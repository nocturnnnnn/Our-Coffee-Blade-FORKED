<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pos;

use App\Enums\CashierShiftStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\CashierShift;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CashierShiftController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $activeShift = $user->activeShift()->first();
        $activeShiftData = null;

        if ($activeShift !== null) {
            $metrics = $this->computeShiftMetrics($activeShift);
            $activeShiftData = [
                'id' => $activeShift->id,
                'opening_cash' => $activeShift->opening_cash,
                'cash_sales_total' => $metrics['cash_sales_total'],
                'non_cash_sales_total' => $metrics['non_cash_sales_total'],
                'expected_cash' => $activeShift->opening_cash + $metrics['cash_sales_total'],
                'orders_count' => $metrics['orders_count'],
                'opened_at' => $activeShift->opened_at->toIso8601String(),
            ];
        }

        $recentShifts = CashierShift::query()
            ->with('user:id,name')
            ->where('status', CashierShiftStatus::Closed->value)
            ->when(! $user->isAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->orderByDesc('closed_at')
            ->limit(10)
            ->get()
            ->map(fn (CashierShift $shift): array => [
                'id' => $shift->id,
                'cashier_name' => $shift->user->name,
                'opening_cash' => $shift->opening_cash,
                'expected_cash' => $shift->expected_cash ?? 0,
                'actual_cash' => $shift->actual_cash ?? 0,
                'variance' => $shift->variance ?? 0,
                'variance_reason' => $shift->variance_reason,
                'opened_at' => $shift->opened_at->toIso8601String(),
                'closed_at' => $shift->closed_at?->toIso8601String(),
            ]);

        return view('pos.shift', [
            'activeShiftDetail' => $activeShiftData,
            'recentShifts' => $recentShifts->values()->all(),
        ]);
    }

    public function open(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        if ($user->activeShift()->exists()) {
            return redirect()->route('pos.index');
        }

        $validated = $request->validate([
            'opening_cash' => ['required', 'integer', 'min:0'],
        ]);

        CashierShift::query()->create([
            'user_id' => $user->id,
            'status' => CashierShiftStatus::Open,
            'opening_cash' => (int) $validated['opening_cash'],
            'opened_at' => now('Asia/Jakarta'),
        ]);

        return redirect()->route('pos.index')->with('success', 'Shift kasir berhasil dibuka.');
    }

    public function close(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $shift = $user->activeShift()->first();
        if ($shift === null) {
            return redirect()->route('pos.shift')->with('error', 'Tidak ada shift aktif yang dapat ditutup.');
        }

        $validated = $request->validate([
            'actual_cash' => ['required', 'integer', 'min:0'],
            'variance_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $metrics = $this->computeShiftMetrics($shift);
        $expectedCash = $shift->opening_cash + $metrics['cash_sales_total'];
        $actualCash = (int) $validated['actual_cash'];
        $variance = $actualCash - $expectedCash;
        $reason = trim((string) ($validated['variance_reason'] ?? ''));

        if ($variance !== 0 && $reason === '') {
            return redirect()->back()->withErrors([
                'variance_reason' => 'Alasan selisih kas wajib diisi apabila terdapat selisih.',
            ]);
        }

        $shift->forceFill([
            'status' => CashierShiftStatus::Closed,
            'expected_cash' => $expectedCash,
            'actual_cash' => $actualCash,
            'variance' => $variance,
            'variance_reason' => $variance !== 0 ? $reason : ($reason !== '' ? $reason : null),
            'closed_at' => now('Asia/Jakarta'),
        ])->save();

        if ($variance !== 0) {
            $this->auditLogService->record(
                action: 'shift.close_variance',
                actorId: $user->id,
                auditable: $shift,
                reason: $reason,
                metadata: [
                    'opening_cash' => $shift->opening_cash,
                    'cash_sales_total' => $metrics['cash_sales_total'],
                    'expected_cash' => $expectedCash,
                    'actual_cash' => $actualCash,
                    'variance' => $variance,
                ],
            );
        }

        return redirect()->route('pos.shift')->with('success', 'Shift kasir berhasil ditutup.');
    }

    /**
     * @return array{cash_sales_total: int, non_cash_sales_total: int, orders_count: int}
     */
    private function computeShiftMetrics(CashierShift $shift): array
    {
        $validOrderIds = Order::query()
            ->where('cashier_shift_id', $shift->id)
            ->whereNotIn('status', [
                OrderStatus::PendingPayment->value,
                OrderStatus::Voided->value,
                OrderStatus::Cancelled->value,
            ])
            ->pluck('id');

        if ($validOrderIds->isEmpty()) {
            return [
                'cash_sales_total' => 0,
                'non_cash_sales_total' => 0,
                'orders_count' => 0,
            ];
        }

        $cashSales = (int) Payment::query()
            ->whereIn('order_id', $validOrderIds)
            ->where('status', PaymentStatus::Paid->value)
            ->where('method', PaymentMethod::Cash->value)
            ->sum('amount');

        $nonCashSales = (int) Payment::query()
            ->whereIn('order_id', $validOrderIds)
            ->where('status', PaymentStatus::Paid->value)
            ->where('method', '!=', PaymentMethod::Cash->value)
            ->sum('amount');

        return [
            'cash_sales_total' => $cashSales,
            'non_cash_sales_total' => $nonCashSales,
            'orders_count' => $validOrderIds->count(),
        ];
    }
}
