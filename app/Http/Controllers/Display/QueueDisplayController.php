<?php

declare(strict_types=1);

namespace App\Http\Controllers\Display;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class QueueDisplayController extends Controller
{
    public function index(): View
    {
        $feed = $this->buildFeed();

        return view('display.queue', [
            'cafeName' => AppSetting::getValue('cafe_name', 'Our Coffee') ?? 'Our Coffee',
            'preparing' => $feed['preparing'],
            'ready' => $feed['ready'],
        ]);
    }

    public function feed(): JsonResponse
    {
        return response()->json($this->buildFeed());
    }

    /**
     * @return array{preparing: array<int, array<string, mixed>>, ready: array<int, array<string, mixed>>}
     */
    private function buildFeed(): array
    {
        $today = now('Asia/Jakarta')->toDateString();

        $preparing = Order::query()
            ->whereDate('queue_date', $today)
            ->whereIn('status', [OrderStatus::Paid->value, OrderStatus::Preparing->value])
            ->orderBy('paid_at')
            ->orderBy('id')
            ->get(['id', 'queue_number', 'customer_name', 'order_type'])
            ->map(fn (Order $order): array => [
                'id' => $order->id,
                'queue_number' => $order->queue_number,
                'customer_name' => $order->customer_name,
                'order_type' => $order->order_type->value,
            ])
            ->values()
            ->all();

        $ready = Order::query()
            ->whereDate('queue_date', $today)
            ->where('status', OrderStatus::Ready->value)
            ->orderByDesc('ready_at')
            ->orderByDesc('id')
            ->get(['id', 'queue_number', 'customer_name', 'order_type'])
            ->map(fn (Order $order): array => [
                'id' => $order->id,
                'queue_number' => $order->queue_number,
                'customer_name' => $order->customer_name,
                'order_type' => $order->order_type->value,
            ])
            ->values()
            ->all();

        return [
            'preparing' => $preparing,
            'ready' => $ready,
        ];
    }
}
