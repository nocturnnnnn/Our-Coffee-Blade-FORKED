<?php

declare(strict_types=1);

namespace App\Http\Controllers\Kds;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemModifier;
use App\Services\OrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KdsController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    public function index(): View
    {
        return view('kds.index', [
            'tickets' => $this->loadActiveTickets(),
            'serverTime' => now('Asia/Jakarta')->toIso8601String(),
        ]);
    }

    public function tickets(): JsonResponse
    {
        return response()->json([
            'tickets' => $this->loadActiveTickets(),
            'serverTime' => now('Asia/Jakarta')->toIso8601String(),
        ]);
    }

    public function transition(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:paid,preparing,ready,completed'],
        ]);

        $updated = $this->orderService->transitionStatus(
            $order,
            OrderStatus::from((string) $validated['status']),
        );

        $updated->load('items.modifiers');

        return response()->json([
            'ticket' => $this->formatTicket($updated),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadActiveTickets(): array
    {
        return Order::query()
            ->with('items.modifiers')
            ->whereIn('status', [
                OrderStatus::Paid->value,
                OrderStatus::Preparing->value,
                OrderStatus::Ready->value,
            ])
            ->orderBy('paid_at')
            ->orderBy('id')
            ->get()
            ->map(fn (Order $order): array => $this->formatTicket($order))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatTicket(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'queue_number' => $order->queue_number,
            'channel' => $order->channel->value,
            'order_type' => $order->order_type->value,
            'customer_name' => $order->customer_name,
            'status' => $order->status->value,
            'paid_at' => $order->paid_at?->toIso8601String(),
            'preparing_at' => $order->preparing_at?->toIso8601String(),
            'ready_at' => $order->ready_at?->toIso8601String(),
            'items' => $order->items->map(fn (OrderItem $item): array => [
                'id' => $item->id,
                'menu_item_name' => $item->menu_item_name,
                'quantity' => $item->quantity,
                'notes' => $item->notes,
                'modifiers' => $item->modifiers->map(fn (OrderItemModifier $mod): array => [
                    'group_name' => $mod->modifier_group_name,
                    'option_name' => $mod->modifier_option_name,
                    'is_default' => $mod->is_default,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }
}
