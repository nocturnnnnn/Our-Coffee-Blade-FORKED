<?php

declare(strict_types=1);

namespace App\Http\Controllers\Kiosk;

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Category;
use App\Models\KioskDevice;
use App\Models\MenuItem;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Order;
use App\Services\AuditLogService;
use App\Services\MenuAvailabilityService;
use App\Services\OrderService;
use App\Services\PinAuthorizationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KioskController extends Controller
{
    public function __construct(
        private readonly MenuAvailabilityService $availabilityService,
        private readonly OrderService $orderService,
        private readonly PinAuthorizationService $pinAuthorizationService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function pair(Request $request, string $token): RedirectResponse
    {
        $device = KioskDevice::query()
            ->where('device_token', $token)
            ->where('is_active', true)
            ->first();

        if ($device === null) {
            abort(404, 'Perangkat Kiosk tidak ditemukan atau tidak aktif.');
        }

        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $device->forceFill(['last_seen_at' => now('Asia/Jakarta')])->saveQuietly();

        return redirect()
            ->route('kiosk.index')
            ->withCookie(cookie(
                name: 'kiosk_device_token',
                value: $device->device_token,
                minutes: 60 * 24 * 365,
                path: '/',
                domain: null,
                secure: $request->isSecure(),
                httpOnly: true,
                raw: false,
                sameSite: 'lax',
            ));
    }

    public function index(Request $request): View
    {
        $device = $this->resolveDevice($request);

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $menuItems = MenuItem::query()
            ->where('is_active', true)
            ->whereIn('category_id', $categories->pluck('id'))
            ->with([
                'recipeItems.ingredient',
                'modifierGroups' => function ($query): void {
                    $query->orderBy('menu_item_modifier_group.sort_order')
                        ->with([
                            'options' => function ($optQuery): void {
                                $optQuery->where('is_available', true)->orderBy('sort_order');
                            },
                        ]);
                },
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $availabilityMap = $this->availabilityService->getAvailabilityMap($menuItems);
        $menusByCategory = $menuItems->groupBy('category_id');

        $catalog = $categories
            ->map(function (Category $category) use ($menusByCategory, $availabilityMap): array {
                $items = ($menusByCategory->get($category->id) ?? collect())->map(
                    function (MenuItem $item) use ($availabilityMap): array {
                        $availability = $availabilityMap[$item->id] ?? [
                            'is_available' => $item->is_available,
                            'reason' => null,
                            'max_servings' => null,
                        ];

                        return [
                            'id' => $item->id,
                            'category_id' => $item->category_id,
                            'name' => $item->name,
                            'description' => $item->description,
                            'price' => $item->price,
                            'image_url' => $item->image_path !== null ? asset('storage/'.$item->image_path) : asset('images/dummy-coffee.svg'),
                            'badge' => $item->badge?->value,
                            'is_available' => $availability['is_available'],
                            'unavailable_reason' => $availability['reason'],
                            'max_servings' => $availability['max_servings'],
                            'modifier_groups' => $item->modifierGroups->map(
                                fn (ModifierGroup $group): array => [
                                    'id' => $group->id,
                                    'name' => $group->name,
                                    'selection_type' => $group->selection_type->value,
                                    'is_required' => $group->is_required,
                                    'min_select' => $group->min_select,
                                    'max_select' => $group->max_select,
                                    'options' => $group->options->map(
                                        fn (ModifierOption $option): array => [
                                            'id' => $option->id,
                                            'name' => $option->name,
                                            'price_delta' => $option->price_delta,
                                            'is_default' => $option->is_default,
                                        ],
                                    )->values()->all(),
                                ],
                            )->values()->all(),
                        ];
                    },
                )->values()->all();

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'icon' => $category->icon,
                    'items' => $items,
                ];
            })
            ->filter(fn (array $cat): bool => count($cat['items']) > 0)
            ->values()
            ->all();

        return view('kiosk.index', [
            'device' => [
                'id' => $device->id,
                'name' => $device->name,
            ],
            'isLocalDemo' => ! app()->isProduction(),
            'settings' => [
                'cafe_name' => AppSetting::getValue('cafe_name', 'Our Coffee') ?? 'Our Coffee',
                'idle_timeout_seconds' => (int) (AppSetting::getValue('kiosk_idle_timeout_seconds', '60') ?? '60'),
                'idle_warning_seconds' => (int) (AppSetting::getValue('kiosk_idle_warning_seconds', '50') ?? '50'),
                'confirmation_reset_seconds' => (int) (AppSetting::getValue('kiosk_confirmation_reset_seconds', '8') ?? '8'),
                'qris_expiry_seconds' => (int) (AppSetting::getValue('qris_expiry_seconds', '300') ?? '300'),
            ],
            'categories' => $catalog,
        ]);
    }

    public function availability(): JsonResponse
    {
        $items = MenuItem::query()
            ->where('is_active', true)
            ->with('recipeItems.ingredient')
            ->get();

        return response()->json([
            'availability' => $this->availabilityService->getAvailabilityMap($items),
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $device = $this->resolveDevice($request);

        $validated = $request->validate([
            'order_type' => ['required', 'in:dine_in,takeaway'],
            'customer_name' => ['nullable', 'string', 'max:80'],
            'payment_method' => ['required', 'in:qris,cashier'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'integer', 'exists:menu_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
            'items.*.modifier_option_ids' => ['nullable', 'array'],
            'items.*.modifier_option_ids.*' => ['integer', 'exists:modifier_options,id'],
        ]);

        /** @var array<int, array{menu_item_id: int, quantity: int, notes?: string|null, modifier_option_ids?: array<int, int>}> $cartItems */
        $cartItems = [];
        foreach ($validated['items'] as $rawItem) {
            $cartItems[] = [
                'menu_item_id' => (int) $rawItem['menu_item_id'],
                'quantity' => (int) $rawItem['quantity'],
                'notes' => isset($rawItem['notes']) && $rawItem['notes'] !== '' ? (string) $rawItem['notes'] : null,
                'modifier_option_ids' => array_map('intval', $rawItem['modifier_option_ids'] ?? []),
            ];
        }

        $order = $this->orderService->createOrder([
            'channel' => OrderChannel::Kiosk,
            'order_type' => OrderType::from((string) $validated['order_type']),
            'customer_name' => isset($validated['customer_name']) && $validated['customer_name'] !== ''
                ? trim((string) $validated['customer_name'])
                : null,
            'kiosk_device_id' => $device->id,
            'items' => $cartItems,
        ]);

        $paymentData = null;
        if ($validated['payment_method'] === 'qris') {
            $payment = $this->orderService->createQrisPayment($order);
            $paymentData = [
                'id' => $payment->id,
                'method' => $payment->method->value,
                'status' => $payment->status->value,
                'qris_reference' => $payment->qris_reference,
                'qris_payload' => $payment->qris_payload,
                'expires_at' => $payment->qris_expires_at?->toIso8601String(),
            ];
        }

        return response()->json([
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'queue_number' => $order->queue_number,
                'status' => $order->status->value,
                'order_type' => $order->order_type->value,
                'customer_name' => $order->customer_name,
                'total_amount' => $order->total_amount,
            ],
            'payment' => $paymentData,
        ], 201);
    }

    public function qrisStatus(Request $request, Order $order): JsonResponse
    {
        $device = $this->resolveDevice($request);
        if ($order->kiosk_device_id !== $device->id) {
            abort(403, 'Pesanan bukan milik perangkat Kiosk ini.');
        }

        $payment = $order->payments()
            ->where('method', PaymentMethod::Qris->value)
            ->orderByDesc('id')
            ->first();

        $paymentStatus = $payment !== null
            ? $this->orderService->refreshQrisStatus($payment)->value
            : PaymentStatus::Pending->value;

        return response()->json([
            'order_status' => $order->status->value,
            'payment_status' => $paymentStatus,
            'queue_number' => $order->queue_number,
            'expires_at' => $payment?->qris_expires_at?->toIso8601String(),
        ]);
    }

    public function regenerateQris(Request $request, Order $order): JsonResponse
    {
        $device = $this->resolveDevice($request);
        if ($order->kiosk_device_id !== $device->id) {
            abort(403, 'Pesanan bukan milik perangkat Kiosk ini.');
        }

        $payment = $this->orderService->createQrisPayment($order);

        return response()->json([
            'payment' => [
                'id' => $payment->id,
                'method' => $payment->method->value,
                'status' => $payment->status->value,
                'qris_reference' => $payment->qris_reference,
                'qris_payload' => $payment->qris_payload,
                'expires_at' => $payment->qris_expires_at?->toIso8601String(),
            ],
        ]);
    }

    public function simulateQrisPaid(Request $request, Order $order): JsonResponse
    {
        if (app()->isProduction()) {
            abort(403);
        }

        $device = $this->resolveDevice($request);
        if ($order->kiosk_device_id !== $device->id) {
            abort(403, 'Pesanan bukan milik perangkat Kiosk ini.');
        }

        if ($order->status === OrderStatus::Paid) {
            return response()->json([
                'order_status' => $order->status->value,
                'payment_status' => PaymentStatus::Paid->value,
                'queue_number' => $order->queue_number,
            ]);
        }

        $payment = $order->payments()
            ->where('method', PaymentMethod::Qris->value)
            ->where('status', PaymentStatus::Pending->value)
            ->orderByDesc('id')
            ->first();

        if ($payment === null) {
            return response()->json([
                'message' => 'Pembayaran QRIS tidak ditemukan atau sudah kedaluwarsa.',
            ], 422);
        }

        $paidOrder = $this->orderService->markPaid(
            order: $order,
            method: PaymentMethod::Qris,
            existingPayment: $payment,
        );

        return response()->json([
            'order_status' => $paidOrder->status->value,
            'payment_status' => PaymentStatus::Paid->value,
            'queue_number' => $paidOrder->queue_number,
        ]);
    }

    public function verifyExitPin(Request $request): JsonResponse
    {
        $device = $this->resolveDevice($request);

        $validated = $request->validate([
            'pin' => ['required', 'string', 'size:6'],
        ]);

        $admin = $this->pinAuthorizationService->verifyAdminPin((string) $validated['pin']);

        $this->auditLogService->record(
            action: 'kiosk.exit',
            actorId: $admin->id,
            authorizedById: $admin->id,
            auditable: $device,
            reason: 'Keluar dari mode Kiosk ke Backoffice',
        );

        return response()->json([
            'redirect' => route('login'),
        ]);
    }

    private function resolveDevice(Request $request): KioskDevice
    {
        $device = $request->attributes->get('kiosk_device');

        if (! $device instanceof KioskDevice) {
            abort(403, 'Perangkat Kiosk tidak terverifikasi.');
        }

        return $device;
    }
}
