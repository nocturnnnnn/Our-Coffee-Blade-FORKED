<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pos;

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemModifier;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\MenuAvailabilityService;
use App\Services\OrderService;
use App\Services\PinAuthorizationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosController extends Controller
{
    public function __construct(
        private readonly MenuAvailabilityService $availabilityService,
        private readonly OrderService $orderService,
        private readonly PinAuthorizationService $pinAuthorizationService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function index(Request $request): View
    {
        $user = $this->resolveUser($request);
        $shift = $user->activeShift()->firstOrFail();

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

        $catalog = $categories->map(function (Category $category) use ($menusByCategory, $availabilityMap): array {
            $items = ($menusByCategory->get($category->id) ?? collect())->map(
                function (MenuItem $item) use ($availabilityMap): array {
                    $avail = $availabilityMap[$item->id] ?? [
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
                        'is_available' => $avail['is_available'],
                        'unavailable_reason' => $avail['reason'],
                        'max_servings' => $avail['max_servings'],
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
        })->filter(fn (array $cat): bool => count($cat['items']) > 0)->values()->all();

        $queues = $this->loadQueues($shift);

        return view('pos.index', [
            'categories' => $catalog,
            'pendingKioskOrders' => $queues['pendingKioskOrders'],
            'recentOrders' => $queues['recentOrders'],
            'cafeSettings' => [
                'cafe_name' => AppSetting::getValue('cafe_name', 'Our Coffee') ?? 'Our Coffee',
                'cafe_address' => AppSetting::getValue('cafe_address', '') ?? '',
                'cafe_phone' => AppSetting::getValue('cafe_phone', '') ?? '',
                'receipt_footer' => AppSetting::getValue('receipt_footer', 'Terima kasih!') ?? 'Terima kasih!',
            ],
        ]);
    }

    public function queue(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);
        $shift = $user->activeShift()->firstOrFail();

        $menuItems = MenuItem::query()
            ->where('is_active', true)
            ->with('recipeItems.ingredient')
            ->get();

        $queues = $this->loadQueues($shift);

        return response()->json([
            'pendingKioskOrders' => $queues['pendingKioskOrders'],
            'recentOrders' => $queues['recentOrders'],
            'availability' => $this->availabilityService->getAvailabilityMap($menuItems),
        ]);
    }

    public function storeWalkInOrder(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);
        $shift = $user->activeShift()->firstOrFail();

        $validated = $request->validate([
            'order_type' => ['required', 'in:dine_in,takeaway'],
            'customer_name' => ['nullable', 'string', 'max:80'],
            'payment_method' => ['required', 'in:cash,edc,qris_manual'],
            'cash_received' => ['nullable', 'integer', 'min:0', 'required_if:payment_method,cash'],
            'discount_amount' => ['nullable', 'integer', 'min:0'],
            'discount_reason' => ['nullable', 'string', 'max:255'],
            'admin_pin' => ['nullable', 'string', 'size:6'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'integer', 'exists:menu_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
            'items.*.modifier_option_ids' => ['nullable', 'array'],
            'items.*.modifier_option_ids.*' => ['integer', 'exists:modifier_options,id'],
        ]);

        $discountAmount = (int) ($validated['discount_amount'] ?? 0);
        $discountReason = trim((string) ($validated['discount_reason'] ?? ''));
        $discountAuthorizedBy = null;

        if ($discountAmount > 0) {
            if ($discountReason === '') {
                throw ValidationException::withMessages([
                    'discount_reason' => 'Alasan diskon manual wajib diisi.',
                ]);
            }

            $authorizer = $this->pinAuthorizationService->verifyAdminPin((string) ($validated['admin_pin'] ?? ''));
            $discountAuthorizedBy = $authorizer->id;
        }

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

        $method = PaymentMethod::from((string) $validated['payment_method']);
        $cashReceived = $method === PaymentMethod::Cash ? (int) $validated['cash_received'] : null;

        $paidOrder = DB::transaction(function () use (
            $validated,
            $user,
            $shift,
            $discountAmount,
            $discountReason,
            $discountAuthorizedBy,
            $cartItems,
            $method,
            $cashReceived,
        ): Order {
            $order = $this->orderService->createOrder([
                'channel' => OrderChannel::Pos,
                'order_type' => OrderType::from((string) $validated['order_type']),
                'customer_name' => isset($validated['customer_name']) && $validated['customer_name'] !== ''
                    ? trim((string) $validated['customer_name'])
                    : null,
                'cashier_shift_id' => $shift->id,
                'cashier_id' => $user->id,
                'discount_amount' => $discountAmount,
                'discount_reason' => $discountAmount > 0 ? $discountReason : null,
                'discount_authorized_by' => $discountAuthorizedBy,
                'items' => $cartItems,
            ]);

            if ($cashReceived !== null && $cashReceived < $order->total_amount) {
                throw ValidationException::withMessages([
                    'cash_received' => 'Nominal uang tunai kurang dari total tagihan.',
                ]);
            }

            return $this->orderService->markPaid(
                order: $order,
                method: $method,
                cashReceived: $cashReceived,
                processedById: $user->id,
                cashierShiftId: $shift->id,
            );
        });

        $paidOrder->load(['items.modifiers', 'payments', 'cashier:id,name']);

        return response()->json([
            'order' => $this->formatOrder($paidOrder),
        ], 201);
    }

    public function settleKioskOrder(Request $request, Order $order): JsonResponse
    {
        $user = $this->resolveUser($request);
        $shift = $user->activeShift()->firstOrFail();

        $validated = $request->validate([
            'payment_method' => ['required', 'in:cash,edc,qris_manual'],
            'cash_received' => ['nullable', 'integer', 'min:0', 'required_if:payment_method,cash'],
            'discount_amount' => ['nullable', 'integer', 'min:0'],
            'discount_reason' => ['nullable', 'string', 'max:255'],
            'admin_pin' => ['nullable', 'string', 'size:6'],
        ]);

        $discountAmount = (int) ($validated['discount_amount'] ?? 0);
        $discountReason = trim((string) ($validated['discount_reason'] ?? ''));
        $discountAuthorizerId = null;

        if ($discountAmount > 0) {
            if ($discountReason === '') {
                throw ValidationException::withMessages([
                    'discount_reason' => 'Alasan diskon manual wajib diisi.',
                ]);
            }

            $authorizer = $this->pinAuthorizationService->verifyAdminPin((string) ($validated['admin_pin'] ?? ''));
            $discountAuthorizerId = $authorizer->id;
        }

        $method = PaymentMethod::from((string) $validated['payment_method']);
        $cashReceived = $method === PaymentMethod::Cash ? (int) $validated['cash_received'] : null;

        $paidOrder = DB::transaction(function () use (
            $order,
            $user,
            $shift,
            $discountAmount,
            $discountReason,
            $discountAuthorizerId,
            $method,
            $cashReceived,
        ): Order {
            if ($discountAmount > 0) {
                $clampedDiscount = min($order->subtotal, $discountAmount);
                $order->forceFill([
                    'discount_amount' => $clampedDiscount,
                    'discount_reason' => $discountReason,
                    'discount_authorized_by' => $discountAuthorizerId,
                    'total_amount' => max(0, $order->subtotal - $clampedDiscount),
                ])->save();

                $this->auditLogService->record(
                    action: 'order.discount',
                    actorId: $user->id,
                    authorizedById: $discountAuthorizerId,
                    auditable: $order,
                    reason: $discountReason,
                    metadata: [
                        'order_number' => $order->order_number,
                        'subtotal' => $order->subtotal,
                        'discount_amount' => $clampedDiscount,
                        'total_amount' => $order->total_amount,
                    ],
                );
            }

            if ($cashReceived !== null && $cashReceived < $order->total_amount) {
                throw ValidationException::withMessages([
                    'cash_received' => 'Nominal uang tunai kurang dari total tagihan.',
                ]);
            }

            return $this->orderService->markPaid(
                order: $order,
                method: $method,
                cashReceived: $cashReceived,
                processedById: $user->id,
                cashierShiftId: $shift->id,
            );
        });

        $paidOrder->load(['items.modifiers', 'payments', 'cashier:id,name']);

        return response()->json([
            'order' => $this->formatOrder($paidOrder),
        ]);
    }

    public function voidOrder(Request $request, Order $order): JsonResponse
    {
        $user = $this->resolveUser($request);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'admin_pin' => ['required', 'string', 'size:6'],
        ]);

        $voided = $this->orderService->voidOrder(
            order: $order,
            reason: (string) $validated['reason'],
            adminPin: (string) $validated['admin_pin'],
            actorId: $user->id,
        );

        $voided->load(['items.modifiers', 'payments', 'cashier:id,name']);

        return response()->json([
            'order' => $this->formatOrder($voided),
        ]);
    }

    /**
     * @return array{pendingKioskOrders: array<int, array<string, mixed>>, recentOrders: array<int, array<string, mixed>>}
     */
    private function loadQueues(CashierShift $shift): array
    {
        $pendingKiosk = Order::query()
            ->with(['items.modifiers', 'payments', 'cashier:id,name'])
            ->where('channel', OrderChannel::Kiosk->value)
            ->where('status', OrderStatus::PendingPayment->value)
            ->orderBy('created_at')
            ->get()
            ->map(fn (Order $order): array => $this->formatOrder($order))
            ->values()
            ->all();

        $recentOrders = Order::query()
            ->with(['items.modifiers', 'payments', 'cashier:id,name'])
            ->where('cashier_shift_id', $shift->id)
            ->orderByDesc('id')
            ->limit(15)
            ->get()
            ->map(fn (Order $order): array => $this->formatOrder($order))
            ->values()
            ->all();

        return [
            'pendingKioskOrders' => $pendingKiosk,
            'recentOrders' => $recentOrders,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatOrder(Order $order): array
    {
        $settledPayment = $order->payments
            ->whereIn('status', [PaymentStatus::Paid, PaymentStatus::Refunded])
            ->sortByDesc('id')
            ->first();

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'queue_number' => $order->queue_number,
            'channel' => $order->channel->value,
            'order_type' => $order->order_type->value,
            'customer_name' => $order->customer_name,
            'status' => $order->status->value,
            'subtotal' => $order->subtotal,
            'discount_amount' => $order->discount_amount,
            'discount_reason' => $order->discount_reason,
            'total_amount' => $order->total_amount,
            'cashier_name' => $order->cashier?->name,
            'paid_at' => $order->paid_at?->toIso8601String(),
            'created_at' => $order->created_at?->toIso8601String(),
            'payment' => $settledPayment !== null ? [
                'method' => $settledPayment->method->value,
                'method_label' => $settledPayment->method->label(),
                'status' => $settledPayment->status->value,
                'amount' => $settledPayment->amount,
                'cash_received' => $settledPayment->cash_received,
                'change_amount' => $settledPayment->change_amount,
            ] : null,
            'items' => $order->items->map(fn (OrderItem $item): array => [
                'id' => $item->id,
                'menu_item_id' => $item->menu_item_id,
                'menu_item_name' => $item->menu_item_name,
                'unit_price' => $item->unit_price,
                'modifiers_total' => $item->modifiers_total,
                'quantity' => $item->quantity,
                'line_total' => $item->line_total,
                'notes' => $item->notes,
                'modifiers' => $item->modifiers->map(fn (OrderItemModifier $mod): array => [
                    'group_name' => $mod->modifier_group_name,
                    'option_name' => $mod->modifier_option_name,
                    'price_delta' => $mod->price_delta,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    private function resolveUser(Request $request): User
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
