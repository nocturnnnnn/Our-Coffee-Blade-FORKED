<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    Ban,
    Banknote,
    CheckCircle2,
    Coffee,
    Percent,
    Printer,
    Search,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ModifierSheet from '@/components/kiosk/ModifierSheet.vue';
import NumPad from '@/components/pos/NumPad.vue';
import SupervisorPinModal from '@/components/pos/SupervisorPinModal.vue';
import ThermalReceipt, {
    type ReceiptOrder,
} from '@/components/pos/ThermalReceipt.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import { usePolling } from '@/composables/usePolling';
import { formatRupiah, formatTimeWib } from '@/lib/format';
import { jsonRequest } from '@/lib/http';
import { cn } from '@/lib/utils';
import type {
    KioskCartItem,
    KioskCategory,
    KioskMenuItem,
} from '@/stores/kiosk-cart-store';

type PosProps = {
    categories: KioskCategory[];
    pendingKioskOrders: ReceiptOrder[];
    recentOrders: ReceiptOrder[];
    cafeSettings: {
        cafe_name: string;
        cafe_address: string;
        cafe_phone: string;
        receipt_footer: string;
    };
};

const props = defineProps<PosProps>();

const categories = ref<KioskCategory[]>(props.categories);
const pendingKioskOrders = ref<ReceiptOrder[]>(props.pendingKioskOrders);
const recentOrders = ref<ReceiptOrder[]>(props.recentOrders);

const col1Tab = ref<'pending' | 'history'>('pending');
const selectedCategoryId = ref<number | null>(null);
const searchQuery = ref('');

const selectedKioskOrder = ref<ReceiptOrder | null>(null);
const walkInCart = ref<KioskCartItem[]>([]);
const walkInOrderType = ref<'dine_in' | 'takeaway'>('dine_in');
const walkInCustomerName = ref('');

const sheetMenu = ref<KioskMenuItem | null>(null);

const paymentMethod = 'cash' as const;
const cashInput = ref('');
const discountAuth = ref<{
    amount: number;
    reason: string;
    pin: string;
} | null>(null);

const pinModal = ref<{
    open: boolean;
    mode: 'discount' | 'void';
    targetOrderId?: number;
}>({ open: false, mode: 'discount' });
const pinError = ref<string | null>(null);
const pinSubmitting = ref(false);

const billError = ref<string | null>(null);
const settling = ref(false);

const receiptOrder = ref<ReceiptOrder | null>(null);

async function refreshQueue() {
    try {
        const res = await jsonRequest<{
            pendingKioskOrders: ReceiptOrder[];
            recentOrders: ReceiptOrder[];
            availability: Record<
                number,
                {
                    is_available: boolean;
                    reason: string | null;
                    max_servings: number | null;
                }
            >;
        }>('/pos/queue');

        if (!res.ok) return;

        pendingKioskOrders.value = res.data.pendingKioskOrders;
        recentOrders.value = res.data.recentOrders;
        categories.value = categories.value.map((cat) => ({
            ...cat,
            items: cat.items.map((item) => {
                const avail = res.data.availability[item.id];
                return avail
                    ? {
                          ...item,
                          is_available: avail.is_available,
                          unavailable_reason: avail.reason,
                          max_servings: avail.max_servings,
                      }
                    : item;
            }),
        }));
    } catch {
        // Silent retry
    }
}

usePolling(refreshQueue, 3000, true);

const filteredMenus = computed(() => {
    const allMenus = categories.value.flatMap((cat) => cat.items);
    return allMenus.filter((item) => {
        if (
            selectedCategoryId.value !== null &&
            item.category_id !== selectedCategoryId.value
        ) {
            return false;
        }
        if (
            searchQuery.value.trim() &&
            !item.name
                .toLowerCase()
                .includes(searchQuery.value.trim().toLowerCase())
        ) {
            return false;
        }
        return true;
    });
});

const walkInSubtotal = computed(() =>
    walkInCart.value.reduce(
        (sum, item) => sum + (item.unitPrice + item.modifiersTotal) * item.quantity,
        0,
    ),
);

const activeSubtotal = computed(() =>
    selectedKioskOrder.value
        ? selectedKioskOrder.value.subtotal
        : walkInSubtotal.value,
);

const activeDiscount = computed(() =>
    discountAuth.value
        ? Math.min(activeSubtotal.value, discountAuth.value.amount)
        : (selectedKioskOrder.value?.discount_amount ?? 0),
);

const activeTotal = computed(() =>
    Math.max(0, activeSubtotal.value - activeDiscount.value),
);

const cashReceivedNum = computed(() => Number(cashInput.value) || 0);
const hasBillItems = computed(
    () => selectedKioskOrder.value !== null || walkInCart.value.length > 0,
);
const canConfirmPayment = computed(
    () =>
        hasBillItems.value &&
        !settling.value &&
        cashReceivedNum.value >= activeTotal.value,
);

function handleAddWalkInItem(
    incoming: Omit<KioskCartItem, 'lineId'> & { lineId?: string },
) {
    selectedKioskOrder.value = null;
    billError.value = null;

    const lineId = `${Date.now()}-${Math.random().toString(36).slice(2, 7)}`;
    walkInCart.value = [...walkInCart.value, { ...incoming, lineId }];
}

async function handleSettle() {
    if (!canConfirmPayment.value) return;

    settling.value = true;
    billError.value = null;

    try {
        const url = selectedKioskOrder.value
            ? `/pos/orders/${selectedKioskOrder.value.id}/pay`
            : '/pos/orders';

        const body = selectedKioskOrder.value
            ? {
                  payment_method: paymentMethod,
                  cash_received: cashReceivedNum.value,
                  discount_amount: discountAuth.value?.amount ?? 0,
                  discount_reason: discountAuth.value?.reason ?? null,
                  admin_pin: discountAuth.value?.pin ?? null,
              }
            : {
                  order_type: walkInOrderType.value,
                  customer_name: walkInCustomerName.value.trim() || null,
                  payment_method: paymentMethod,
                  cash_received: cashReceivedNum.value,
                  discount_amount: discountAuth.value?.amount ?? 0,
                  discount_reason: discountAuth.value?.reason ?? null,
                  admin_pin: discountAuth.value?.pin ?? null,
                  items: walkInCart.value.map((item) => ({
                      menu_item_id: item.menuItemId,
                      quantity: item.quantity,
                      notes: item.notes || null,
                      modifier_option_ids: item.selectedModifiers.map(
                          (m) => m.optionId,
                      ),
                  })),
              };

        const res = await jsonRequest<{
            order?: ReceiptOrder;
            message?: string;
        }>(url, { method: 'POST', body });

        if (!res.ok || !res.data.order) {
            billError.value =
                res.data.message ?? 'Gagal menyelesaikan pembayaran.';
            return;
        }

        receiptOrder.value = res.data.order;
        selectedKioskOrder.value = null;
        walkInCart.value = [];
        walkInCustomerName.value = '';
        cashInput.value = '';
        discountAuth.value = null;
        await refreshQueue();
    } catch {
        billError.value =
            'Terjadi kesalahan jaringan saat memproses pembayaran.';
    } finally {
        settling.value = false;
    }
}

async function handlePinModalConfirm(payload: {
    pin: string;
    reason: string;
    discountAmount?: number;
}) {
    pinError.value = null;

    if (pinModal.value.mode === 'discount') {
        discountAuth.value = {
            amount: payload.discountAmount ?? 0,
            reason: payload.reason,
            pin: payload.pin,
        };
        pinModal.value = { open: false, mode: 'discount' };
        return;
    }

    if (pinModal.value.mode === 'void' && pinModal.value.targetOrderId) {
        pinSubmitting.value = true;
        try {
            const res = await jsonRequest<{ message?: string }>(
                `/pos/orders/${pinModal.value.targetOrderId}/void`,
                {
                    method: 'POST',
                    body: {
                        reason: payload.reason,
                        admin_pin: payload.pin,
                    },
                },
            );

            if (!res.ok) {
                pinError.value = res.data.message ?? 'PIN Admin tidak valid.';
                return;
            }

            pinModal.value = { open: false, mode: 'void' };
            await refreshQueue();
        } catch {
            pinError.value = 'Gagal memproses void pesanan.';
        } finally {
            pinSubmitting.value = false;
        }
    }
}
</script>

<template>
    <Head title="POS Kasir" />
    <div class="grid h-[calc(100vh-3rem)] grid-cols-12 gap-4 overflow-hidden">
        <!-- COLUMN 1: Pending Kiosk & Shift History -->
        <section class="col-span-3 flex min-h-0 flex-col overflow-hidden rounded-2xl border bg-card shadow-xs">
            <div class="grid grid-cols-2 gap-1 border-b p-2">
                <button
                    type="button"
                    @click="col1Tab = 'pending'"
                    :class="
                        cn(
                            'min-h-12 rounded-xl px-2 text-xs font-bold transition-colors',
                            col1Tab === 'pending'
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-muted',
                        )
                    "
                >
                    Pending Kiosk ({{ pendingKioskOrders.length }})
                </button>
                <button
                    type="button"
                    @click="col1Tab = 'history'"
                    :class="
                        cn(
                            'min-h-12 rounded-xl px-2 text-xs font-bold transition-colors',
                            col1Tab === 'history'
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-muted',
                        )
                    "
                >
                    Riwayat ({{ recentOrders.length }})
                </button>
            </div>

            <div class="flex-1 space-y-2 overflow-y-auto p-3">
                <template v-if="col1Tab === 'pending'">
                    <p
                        v-if="pendingKioskOrders.length === 0"
                        class="py-10 text-center text-xs text-muted-foreground"
                    >
                        Tidak ada pesanan Kiosk menunggu bayar.
                    </p>
                    <button
                        v-else
                        v-for="order in pendingKioskOrders"
                        :key="order.id"
                        type="button"
                        @click="
                            selectedKioskOrder = order;
                            walkInCart = [];
                            discountAuth = null;
                            cashInput = '';
                            billError = null;
                        "
                        :class="
                            cn(
                                'min-h-12 w-full rounded-xl border p-3 text-left transition-colors',
                                selectedKioskOrder?.id === order.id
                                    ? 'border-primary bg-primary/10'
                                    : 'hover:bg-muted/50',
                            )
                        "
                    >
                        <div class="flex items-center justify-between">
                            <span class="font-money text-base font-extrabold text-primary">
                                #{{ order.queue_number }}
                            </span>
                            <Badge variant="secondary">
                                {{
                                    order.order_type === 'dine_in'
                                        ? 'Dine-In'
                                        : 'Takeaway'
                                }}
                            </Badge>
                        </div>
                        <p class="mt-1 text-xs font-semibold">
                            {{ order.customer_name || 'Pelanggan Kiosk' }}
                        </p>
                        <div class="mt-1.5 flex items-center justify-between text-xs text-muted-foreground">
                            <span>{{ order.items.length }} item</span>
                            <span class="font-money font-bold text-foreground">
                                {{ formatRupiah(order.total_amount) }}
                            </span>
                        </div>
                    </button>
                </template>

                <template v-else>
                    <div
                        v-for="order in recentOrders"
                        :key="order.id"
                        class="space-y-2 rounded-xl border p-3 text-xs"
                    >
                        <div class="flex items-center justify-between">
                            <span class="font-money font-extrabold">
                                #{{ order.queue_number }}
                            </span>
                            <Badge
                                :variant="
                                    order.status === 'voided'
                                        ? 'destructive'
                                        : 'outline'
                                "
                            >
                                {{ order.status }}
                            </Badge>
                        </div>
                        <div class="flex items-center justify-between text-muted-foreground">
                            <span>
                                {{ order.paid_at ? formatTimeWib(order.paid_at) : '' }}
                            </span>
                            <span class="font-money font-bold text-foreground">
                                {{ formatRupiah(order.total_amount) }}
                            </span>
                        </div>
                        <div class="flex gap-1.5">
                            <Button
                                size="sm"
                                variant="outline"
                                @click="receiptOrder = order"
                                class="min-h-12 flex-1 gap-1 text-xs"
                            >
                                <Printer class="size-3.5" />
                                Struk
                            </Button>
                            <Button
                                v-if="order.status !== 'voided'"
                                size="sm"
                                variant="outline"
                                @click="
                                    pinError = null;
                                    pinModal = {
                                        open: true,
                                        mode: 'void',
                                        targetOrderId: order.id,
                                    };
                                "
                                class="min-h-12 gap-1 text-xs text-destructive"
                            >
                                <Ban class="size-3.5" />
                                Void
                            </Button>
                        </div>
                    </div>
                </template>
            </div>
        </section>

        <!-- COLUMN 2: Walk-In Menu Grid -->
        <section class="col-span-5 flex min-h-0 flex-col overflow-hidden rounded-2xl border bg-card shadow-xs">
            <div class="space-y-2.5 border-b p-3">
                <div class="relative">
                    <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        v-model="searchQuery"
                        placeholder="Cari menu walk-in..."
                        class="h-12 pl-9"
                    />
                </div>
                <div class="flex gap-1.5 overflow-x-auto pb-1">
                    <button
                        type="button"
                        @click="selectedCategoryId = null"
                        :class="
                            cn(
                                'min-h-12 shrink-0 rounded-xl px-3.5 text-xs font-bold transition-colors',
                                selectedCategoryId === null
                                    ? 'bg-primary text-primary-foreground shadow-xs'
                                    : 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
                            )
                        "
                    >
                        Semua
                    </button>
                    <button
                        v-for="cat in categories"
                        :key="cat.id"
                        type="button"
                        @click="selectedCategoryId = cat.id"
                        :class="
                            cn(
                                'min-h-12 shrink-0 rounded-xl px-3.5 text-xs font-bold transition-colors',
                                selectedCategoryId === cat.id
                                    ? 'bg-primary text-primary-foreground shadow-xs'
                                    : 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
                            )
                        "
                    >
                        {{ cat.name }}
                    </button>
                </div>
            </div>

            <div class="grid flex-1 grid-cols-2 content-start gap-3 overflow-y-auto p-3">
                <button
                    v-for="item in filteredMenus"
                    :key="item.id"
                    type="button"
                    :disabled="!item.is_available"
                    @click="
                        if (item.modifier_groups.length === 0) {
                            handleAddWalkInItem({
                                menuItemId: item.id,
                                name: item.name,
                                imageUrl: item.image_url,
                                unitPrice: item.price,
                                modifiersTotal: 0,
                                quantity: 1,
                                notes: '',
                                selectedModifiers: [],
                            });
                        } else {
                            sheetMenu = item;
                        }
                    "
                    :class="
                        cn(
                            'flex min-h-24 flex-col justify-between rounded-xl border bg-background p-3 text-left transition-all',
                            item.is_available
                                ? 'hover:border-primary hover:bg-primary/5'
                                : 'cursor-not-allowed opacity-50',
                        )
                    "
                >
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-sm leading-snug font-bold">{{ item.name }}</p>
                        <Badge v-if="!item.is_available" variant="destructive">
                            HABIS
                        </Badge>
                        <Coffee v-else class="size-4 shrink-0 text-muted-foreground" />
                    </div>
                    <p class="font-money mt-2 text-sm font-extrabold text-primary">
                        {{ formatRupiah(item.price) }}
                    </p>
                </button>
            </div>
        </section>

        <!-- COLUMN 3: Active Bill, NumPad & Settlement -->
        <section class="col-span-4 flex min-h-0 flex-col overflow-hidden rounded-2xl border bg-card shadow-xs">
            <div class="flex items-center justify-between border-b px-4 py-3">
                <div>
                    <h2 class="text-base font-extrabold">
                        {{
                            selectedKioskOrder
                                ? `Tiket Kiosk #${selectedKioskOrder.queue_number}`
                                : 'Pesanan Walk-In'
                        }}
                    </h2>
                    <p class="text-xs text-muted-foreground">
                        {{
                            selectedKioskOrder
                                ? selectedKioskOrder.order_number
                                : 'Pilih menu di kolom tengah'
                        }}
                    </p>
                </div>
                <Button
                    v-if="selectedKioskOrder || walkInCart.length > 0"
                    variant="ghost"
                    size="sm"
                    @click="
                        selectedKioskOrder = null;
                        walkInCart = [];
                        discountAuth = null;
                        cashInput = '';
                    "
                    class="min-h-12 text-xs text-destructive"
                >
                    Reset
                </Button>
            </div>

            <div class="flex-1 space-y-3 overflow-y-auto p-4">
                <div v-if="!selectedKioskOrder" class="grid grid-cols-2 gap-2">
                    <button
                        type="button"
                        @click="walkInOrderType = 'dine_in'"
                        :class="
                            cn(
                                'min-h-12 rounded-xl border text-xs font-bold',
                                walkInOrderType === 'dine_in'
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'bg-background',
                            )
                        "
                    >
                        Dine-In
                    </button>
                    <button
                        type="button"
                        @click="walkInOrderType = 'takeaway'"
                        :class="
                            cn(
                                'min-h-12 rounded-xl border text-xs font-bold',
                                walkInOrderType === 'takeaway'
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'bg-background',
                            )
                        "
                    >
                        Takeaway
                    </button>
                    <Input
                        v-model="walkInCustomerName"
                        placeholder="Nama pelanggan (opsional)"
                        class="col-span-2 h-12 text-xs"
                    />
                </div>

                <p
                    v-if="billError"
                    class="rounded-xl bg-red-50 px-3 py-2 text-xs font-semibold text-red-700"
                >
                    {{ billError }}
                </p>

                <div v-if="selectedKioskOrder" class="space-y-2">
                    <div
                        v-for="item in selectedKioskOrder.items"
                        :key="item.id"
                        class="flex justify-between rounded-xl border bg-muted/30 p-2.5 text-xs"
                    >
                        <div>
                            <p class="font-bold">
                                {{ item.quantity }}x {{ item.menu_item_name }}
                            </p>
                            <p
                                v-if="item.modifiers.length > 0"
                                class="text-muted-foreground"
                            >
                                {{ item.modifiers.map((m) => m.option_name).join(', ') }}
                            </p>
                        </div>
                        <span class="font-money font-bold">
                            {{ formatRupiah(item.line_total) }}
                        </span>
                    </div>
                </div>

                <p
                    v-else-if="walkInCart.length === 0"
                    class="py-6 text-center text-xs text-muted-foreground"
                >
                    Belum ada item di keranjang POS.
                </p>

                <div v-else class="space-y-2">
                    <div
                        v-for="item in walkInCart"
                        :key="item.lineId"
                        class="flex items-center justify-between gap-2 rounded-xl border bg-muted/30 p-2.5 text-xs"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-bold">
                                {{ item.quantity }}x {{ item.name }}
                            </p>
                            <p
                                v-if="item.selectedModifiers.length > 0"
                                class="truncate text-muted-foreground"
                            >
                                {{
                                    item.selectedModifiers
                                        .map((m) => m.optionName)
                                        .join(', ')
                                }}
                            </p>
                        </div>
                        <span class="font-money font-bold">
                            {{
                                formatRupiah(
                                    (item.unitPrice + item.modifiersTotal) *
                                        item.quantity,
                                )
                            }}
                        </span>
                        <Button
                            size="sm"
                            variant="ghost"
                            @click="
                                walkInCart = walkInCart.filter(
                                    (c) => c.lineId !== item.lineId,
                                )
                            "
                            class="min-h-12 min-w-12"
                            aria-label="Hapus"
                        >
                            <Trash2 class="size-4 text-destructive" />
                        </Button>
                    </div>
                </div>

                <template v-if="hasBillItems">
                    <div class="space-y-1.5 rounded-xl border bg-muted/40 p-3 text-xs">
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                            <span class="font-money font-semibold">
                                {{ formatRupiah(activeSubtotal) }}
                            </span>
                        </div>
                        <div
                            v-if="activeDiscount > 0"
                            class="flex justify-between text-emerald-700"
                        >
                            <span>Diskon Manual</span>
                            <span class="font-money font-semibold">
                                -{{ formatRupiah(activeDiscount) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between border-t pt-1.5 text-base font-extrabold">
                            <span>Total Tagihan</span>
                            <span class="font-money text-primary">
                                {{ formatRupiah(activeTotal) }}
                            </span>
                        </div>
                    </div>

                    <Button
                        variant="outline"
                        @click="
                            pinError = null;
                            pinModal = { open: true, mode: 'discount' };
                        "
                        class="min-h-12 w-full gap-2 text-xs font-bold"
                    >
                        <Percent class="size-4" />
                        {{
                            discountAuth
                                ? `Ubah Diskon (${formatRupiah(discountAuth.amount)})`
                                : 'Terapkan Diskon Manual (PIN Admin)'
                        }}
                    </Button>

                    <div class="grid grid-cols-1">
                        <div class="flex min-h-12 items-center justify-center gap-2 rounded-xl border border-primary bg-primary text-sm font-bold text-white shadow-xs">
                            <Banknote class="size-4" />
                            Pembayaran Tunai (Cash Only)
                        </div>
                    </div>

                    <NumPad :total-amount="activeTotal" v-model="cashInput" />
                </template>
            </div>

            <div class="border-t p-3">
                <Button
                    :disabled="!canConfirmPayment"
                    @click="handleSettle"
                    class="min-h-14 w-full gap-2 rounded-xl text-base font-extrabold"
                >
                    <CheckCircle2 class="size-5" />
                    {{
                        settling
                            ? 'Memproses...'
                            : `Lunasi ${formatRupiah(activeTotal)}`
                    }}
                </Button>
            </div>
        </section>
    </div>

    <ModifierSheet
        :menu-item="sheetMenu"
        :open="sheetMenu !== null"
        @close="sheetMenu = null"
        @confirm="handleAddWalkInItem"
    />

    <SupervisorPinModal
        :open="pinModal.open"
        :mode="pinModal.mode"
        :max-discount="activeSubtotal"
        :error-message="pinError"
        :submitting="pinSubmitting"
        @close="pinModal.open = false"
        @confirm="handlePinModalConfirm"
    />

    <ThermalReceipt
        :order="receiptOrder"
        :cafe-settings="cafeSettings"
        :open="receiptOrder !== null"
        @close="receiptOrder = null"
    />
</template>
