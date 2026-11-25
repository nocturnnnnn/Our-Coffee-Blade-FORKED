<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        [$startDate, $endDate, $startAt, $endAt] = $this->resolveDateRange($request);

        $settledStatuses = [
            OrderStatus::Paid->value,
            OrderStatus::Preparing->value,
            OrderStatus::Ready->value,
            OrderStatus::Completed->value,
        ];

        $settledOrders = Order::query()
            ->whereIn('status', $settledStatuses)
            ->whereBetween('paid_at', [$startAt, $endAt])
            ->orderBy('paid_at')
            ->get();

        $voidedCount = Order::query()
            ->where('status', OrderStatus::Voided->value)
            ->whereBetween('created_at', [$startAt, $endAt])
            ->count();

        $grossSales = (int) $settledOrders->sum('subtotal');
        $totalDiscount = (int) $settledOrders->sum('discount_amount');
        $netRevenue = (int) $settledOrders->sum('total_amount');
        $totalCogs = (int) $settledOrders->sum('cogs_total');
        $grossProfit = $netRevenue - $totalCogs;
        $marginPercent = $netRevenue > 0 ? round(($grossProfit / $netRevenue) * 100, 1) : 0.0;

        /** @var array<string, array{date: string, orders: int, net_revenue: int, cogs: int, profit: int}> $dailyMap */
        $dailyMap = [];
        foreach ($settledOrders as $order) {
            $dateKey = $order->paid_at?->setTimezone('Asia/Jakarta')->format('Y-m-d') ?? $startDate;
            if (! isset($dailyMap[$dateKey])) {
                $dailyMap[$dateKey] = [
                    'date' => $dateKey,
                    'orders' => 0,
                    'net_revenue' => 0,
                    'cogs' => 0,
                    'profit' => 0,
                ];
            }

            $dailyMap[$dateKey]['orders']++;
            $dailyMap[$dateKey]['net_revenue'] += $order->total_amount;
            $dailyMap[$dateKey]['cogs'] += $order->cogs_total;
            $dailyMap[$dateKey]['profit'] += ($order->total_amount - $order->cogs_total);
        }

        /** @var array<int, array{menu_item_id: int, name: string, qty_sold: int, revenue: int, cogs: int, gross_profit: int, margin_percent: float}> $menuRows */
        $menuRows = [];
        if ($settledOrders->isNotEmpty()) {
            $items = OrderItem::query()
                ->whereIn('order_id', $settledOrders->pluck('id'))
                ->get(['menu_item_id', 'menu_item_name', 'quantity', 'line_total', 'unit_cogs']);

            foreach ($items as $item) {
                if (! isset($menuRows[$item->menu_item_id])) {
                    $menuRows[$item->menu_item_id] = [
                        'menu_item_id' => $item->menu_item_id,
                        'name' => $item->menu_item_name,
                        'qty_sold' => 0,
                        'revenue' => 0,
                        'cogs' => 0,
                        'gross_profit' => 0,
                        'margin_percent' => 0.0,
                    ];
                }

                $menuRows[$item->menu_item_id]['qty_sold'] += $item->quantity;
                $menuRows[$item->menu_item_id]['revenue'] += $item->line_total;
                $menuRows[$item->menu_item_id]['cogs'] += ($item->unit_cogs * $item->quantity);
            }

            foreach ($menuRows as $id => $row) {
                $profit = $row['revenue'] - $row['cogs'];
                $menuRows[$id]['gross_profit'] = $profit;
                $menuRows[$id]['margin_percent'] = $row['revenue'] > 0
                    ? round(($profit / $row['revenue']) * 100, 1)
                    : 0.0;
            }
        }

        usort($menuRows, fn (array $a, array $b): int => $b['revenue'] <=> $a['revenue']);

        return view('admin.reports.index', [
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'summary' => [
                'gross_sales' => $grossSales,
                'total_discount' => $totalDiscount,
                'net_revenue' => $netRevenue,
                'total_cogs' => $totalCogs,
                'gross_profit' => $grossProfit,
                'gross_margin_percent' => $marginPercent,
                'orders_count' => $settledOrders->count(),
                'voided_count' => $voidedCount,
            ],
            'dailyRows' => array_values($dailyMap),
            'menuRows' => $menuRows,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$startDate, $endDate, $startAt, $endAt] = $this->resolveDateRange($request);

        $orders = Order::query()
            ->with(['cashier:id,name', 'payments'])
            ->whereBetween('created_at', [$startAt, $endAt])
            ->orderBy('created_at')
            ->get();

        $filename = sprintf('our-coffee-report-%s-to-%s.csv', $startDate, $endDate);

        return response()->streamDownload(function () use ($orders): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Order Number',
                'Queue Number',
                'Tanggal (WIB)',
                'Channel',
                'Tipe',
                'Status',
                'Pelanggan',
                'Kasir',
                'Subtotal (Rp)',
                'Diskon (Rp)',
                'Total (Rp)',
                'HPP (Rp)',
                'Laba Kotor (Rp)',
            ]);

            foreach ($orders as $order) {
                $timestamp = ($order->paid_at ?? $order->created_at)?->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s') ?? '';
                $cashier = $order->cashier;
                fputcsv($handle, [
                    $order->order_number,
                    $order->queue_number,
                    $timestamp,
                    $order->channel->label(),
                    $order->order_type->label(),
                    $order->status->label(),
                    $order->customer_name ?? '-',
                    $cashier !== null ? $cashier->name : '-',
                    $order->subtotal,
                    $order->discount_amount,
                    $order->total_amount,
                    $order->cogs_total,
                    $order->total_amount - $order->cogs_total,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{0: string, 1: string, 2: CarbonImmutable, 3: CarbonImmutable}
     */
    private function resolveDateRange(Request $request): array
    {
        $now = now('Asia/Jakarta');
        $defaultStart = $now->subDays(6)->format('Y-m-d');
        $defaultEnd = $now->format('Y-m-d');

        $rawStart = (string) $request->query('start_date', $defaultStart);
        $rawEnd = (string) $request->query('end_date', $defaultEnd);

        $parsedStart = CarbonImmutable::createFromFormat('Y-m-d', $rawStart, 'Asia/Jakarta');
        $parsedEnd = CarbonImmutable::createFromFormat('Y-m-d', $rawEnd, 'Asia/Jakarta');

        if ($parsedStart === null || $parsedEnd === null) {
            $parsedStart = $now->subDays(6);
            $parsedEnd = $now;
        }

        if ($parsedStart->gt($parsedEnd)) {
            [$parsedStart, $parsedEnd] = [$parsedEnd, $parsedStart];
        }

        return [
            $parsedStart->format('Y-m-d'),
            $parsedEnd->format('Y-m-d'),
            $parsedStart->startOfDay(),
            $parsedEnd->endOfDay(),
        ];
    }
}
