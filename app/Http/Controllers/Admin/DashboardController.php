<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $days = (int) $request->query('days', 7) === 30 ? 30 : 7;

        return view('admin.dashboard', $this->buildDashboardData($days));
    }

    public function metrics(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 7) === 30 ? 30 : 7;

        return response()->json($this->buildDashboardData($days));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDashboardData(int $days): array
    {
        $now = now('Asia/Jakarta');
        $todayStart = $now->startOfDay();
        $todayEnd = $now->endOfDay();
        $rangeStart = $now->subDays($days - 1)->startOfDay();

        $settledStatuses = [
            OrderStatus::Paid->value,
            OrderStatus::Preparing->value,
            OrderStatus::Ready->value,
            OrderStatus::Completed->value,
        ];

        $todayOrders = Order::query()
            ->whereIn('status', $settledStatuses)
            ->whereBetween('paid_at', [$todayStart, $todayEnd])
            ->get(['total_amount']);

        $todayRevenue = (int) $todayOrders->sum('total_amount');
        $todayCount = $todayOrders->count();
        $todayAov = $todayCount > 0 ? (int) round($todayRevenue / $todayCount) : 0;

        $lowStockAll = Ingredient::query()
            ->where('is_active', true)
            ->whereColumn('current_stock', '<=', 'min_stock')
            ->orderByRaw('current_stock - min_stock ASC')
            ->get();

        $rangeOrders = Order::query()
            ->whereIn('status', $settledStatuses)
            ->whereBetween('paid_at', [$rangeStart, $todayEnd])
            ->get(['id', 'channel', 'total_amount', 'paid_at']);

        // Revenue trend map
        /** @var array<string, array{date: string, label: string, revenue: int, orders: int}> $trendMap */
        $trendMap = [];
        for ($i = 0; $i < $days; $i++) {
            $d = $rangeStart->addDays($i);
            $key = $d->format('Y-m-d');
            $trendMap[$key] = [
                'date' => $key,
                'label' => $d->format('d/m'),
                'revenue' => 0,
                'orders' => 0,
            ];
        }

        // Peak hours 0..23
        /** @var array<int, array{hour: int, label: string, orders: int}> $peakHours */
        $peakHours = [];
        for ($h = 0; $h < 24; $h++) {
            $peakHours[$h] = [
                'hour' => $h,
                'label' => str_pad((string) $h, 2, '0', STR_PAD_LEFT).':00',
                'orders' => 0,
            ];
        }

        $kioskOrders = 0;
        $kioskRevenue = 0;
        $posOrders = 0;
        $posRevenue = 0;

        foreach ($rangeOrders as $order) {
            if ($order->channel === OrderChannel::Kiosk) {
                $kioskOrders++;
                $kioskRevenue += $order->total_amount;
            } else {
                $posOrders++;
                $posRevenue += $order->total_amount;
            }

            if ($order->paid_at !== null) {
                $paidWib = $order->paid_at->setTimezone('Asia/Jakarta');
                $dateKey = $paidWib->format('Y-m-d');
                if (isset($trendMap[$dateKey])) {
                    $trendMap[$dateKey]['revenue'] += $order->total_amount;
                    $trendMap[$dateKey]['orders']++;
                }

                $hour = (int) $paidWib->format('G');
                $peakHours[$hour]['orders']++;
            }
        }

        // Top 5 menus with margin
        $orderIds = $rangeOrders->pluck('id');
        /** @var array<int, array{menu_item_id: int, name: string, qty_sold: int, revenue: int, cogs: int}> $menuAgg */
        $menuAgg = [];
        if ($orderIds->isNotEmpty()) {
            $items = OrderItem::query()
                ->whereIn('order_id', $orderIds)
                ->get(['menu_item_id', 'menu_item_name', 'quantity', 'line_total', 'unit_cogs']);

            foreach ($items as $item) {
                if (! isset($menuAgg[$item->menu_item_id])) {
                    $menuAgg[$item->menu_item_id] = [
                        'menu_item_id' => $item->menu_item_id,
                        'name' => $item->menu_item_name,
                        'qty_sold' => 0,
                        'revenue' => 0,
                        'cogs' => 0,
                    ];
                }

                $menuAgg[$item->menu_item_id]['qty_sold'] += $item->quantity;
                $menuAgg[$item->menu_item_id]['revenue'] += $item->line_total;
                $menuAgg[$item->menu_item_id]['cogs'] += ($item->unit_cogs * $item->quantity);
            }
        }

        usort($menuAgg, fn (array $a, array $b): int => $b['revenue'] <=> $a['revenue']);
        $topMenus = array_map(function (array $row): array {
            $profit = $row['revenue'] - $row['cogs'];
            $margin = $row['revenue'] > 0 ? round(($profit / $row['revenue']) * 100, 1) : 0.0;

            return [
                ...$row,
                'gross_profit' => $profit,
                'margin_percent' => $margin,
            ];
        }, array_slice($menuAgg, 0, 5));

        return [
            'days' => $days,
            'kpis' => [
                'today_revenue' => $todayRevenue,
                'today_orders' => $todayCount,
                'today_aov' => $todayAov,
                'low_stock_count' => $lowStockAll->count(),
            ],
            'revenueTrend' => array_values($trendMap),
            'channelSplit' => [
                'kiosk_orders' => $kioskOrders,
                'kiosk_revenue' => $kioskRevenue,
                'pos_orders' => $posOrders,
                'pos_revenue' => $posRevenue,
            ],
            'peakHours' => array_values($peakHours),
            'topMenus' => $topMenus,
            'lowStockIngredients' => $lowStockAll->take(8)->map(fn (Ingredient $ing): array => [
                'id' => $ing->id,
                'name' => $ing->name,
                'unit' => $ing->unit->value,
                'current_stock' => (float) $ing->current_stock,
                'min_stock' => (float) $ing->min_stock,
            ])->values()->all(),
        ];
    }
}
