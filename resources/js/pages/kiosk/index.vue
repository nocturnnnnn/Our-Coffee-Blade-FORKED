<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowLeft,
    Banknote,
    CheckCircle2,
    Coffee,
    Croissant,
    CupSoda,
    Edit2,
    Minus,
    Plus,
    RefreshCw,
    ShoppingBag,
    Sparkles,
    Store,
    Trash2,
    Utensils,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import ModifierSheet from '@/components/kiosk/ModifierSheet.vue';
import PinExitModal from '@/components/kiosk/PinExitModal.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import { usePolling } from '@/composables/usePolling';
import { formatRupiah } from '@/lib/format';
import { jsonRequest } from '@/lib/http';
import { cn } from '@/lib/utils';
import {
    kioskCartStore,
    type KioskCartItem,
    type KioskCategory,
    type KioskMenuItem,
} from '@/stores/kiosk-cart-store';

type KioskProps = {
    device: {
        id: number;
        name: string;
    };
    isLocalDemo: boolean;
    settings: {
        cafe_name: string;
        idle_timeout_seconds: number;
        idle_warning_seconds: number;
        confirmation_reset_seconds: number;
        qris_expiry_seconds: number;
    };
    categories: KioskCategory[];
};

type ActiveOrder = {
    id: number;
    order_number: string;
    queue_number: string;
    status: string;
    order_type: string;
    customer_name: string | null;
    total_amount: number;
};

type ActivePayment = {
    id: number;
    method: string;
    status: string;
    qris_reference: string | null;
    qris_payload: string | null;
    expires_at: string | null;
};

type ConflictEntry = {
    menu_item_id: number;
    menu_item_name: string;
    reason: string;
};

const props = defineProps<KioskProps>();

const categories = ref<KioskCategory[]>(props.categories);
const activeCategoryId = ref<number>(props.categories[0]?.id ?? 0);

const sheetMenu = ref<KioskMenuItem | null>(null);
const editingCartItem = ref<KioskCartItem | null>(null);

const activeOrder = ref<ActiveOrder | null>(null);
const activePayment = ref<ActivePayment | null>(null);
const qrisSecondsLeft = ref(0);
const submittingCheckout = ref(false);
const checkoutError = ref<string | null>(null);
const conflicts = ref<ConflictEntry[]>([]);

const confirmationSecondsLeft = ref(props.settings.confirmation_reset_seconds);
const confirmationStatus = ref<'paid' | 'pending_cashier'>('paid');

const idleSeconds = ref(0);
const pinExitOpen = ref(false);

let tapCount = 0;
let tapWindowTimer: number | null = null;
let longPressTimer: number | null = null;
let idleInterval: number | null = null;
let qrisTimer: number | null = null;
let confirmationTimer: number | null = null;

function categoryIconComponent(icon: string | null) {
    switch (icon) {
        case 'sparkles':
            return Sparkles;
        case 'cup-soda':
            return CupSoda;
        case 'croissant':
            return Croissant;
        case 'utensils':
            return Utensils;
        default:
            return Coffee;
    }
}

const qrCells = computed(() => {
    const payload = activePayment.value?.qris_payload ?? '';
    const size = 21;
    const grid: boolean[][] = [];
    let seed = 0;
    for (let i = 0; i < payload.length; i++) {
        seed = (seed * 31 + payload.charCodeAt(i)) >>> 0;
    }

    const isFinder = (r: number, c: number) =>
        (r < 7 && c < 7) ||
        (r < 7 && c >= size - 7) ||
        (r >= size - 7 && c < 7);

    for (let r = 0; r < size; r++) {
        const row: boolean[] = [];
        for (let c = 0; c < size; c++) {
            if (isFinder(r, c)) {
                const lr = r >= size - 7 ? r - (size - 7) : r;
                const lc = c >= size - 7 ? c - (size - 7) : c;
                const outer = lr === 0 || lr === 6 || lc === 0 || lc === 6;
                const inner = lr >= 2 && lr <= 4 && lc >= 2 && lc <= 4;
                row.push(outer || inner);
            } else {
                seed = (seed * 1664525 + 1013904223) >>> 0;
                row.push(((seed >>> 24) & 1) === 1);
            }
        }
        grid.push(row);
    }
    return grid;
});

function resetAllState() {
    kioskCartStore.resetSession();
    activeOrder.value = null;
    activePayment.value = null;
    conflicts.value = [];
    checkoutError.value = null;
    idleSeconds.value = 0;
}

function handleUserActivity() {
    idleSeconds.value = 0;
}

function handleWheel(e: WheelEvent) {
    if (e.ctrlKey || e.metaKey) {
        e.preventDefault();
    }
}

function handleKeyDown(e: KeyboardEvent) {
    if ((e.ctrlKey || e.metaKey) && ['+', '-', '=', '0'].includes(e.key)) {
        e.preventDefault();
    }
}

onMounted(() => {
    window.addEventListener('wheel', handleWheel, { passive: false });
    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('wheel', handleWheel);
    window.removeEventListener('keydown', handleKeyDown);
    if (idleInterval !== null) window.clearInterval(idleInterval);
    if (qrisTimer !== null) window.clearInterval(qrisTimer);
    if (confirmationTimer !== null) window.clearInterval(confirmationTimer);
});

watch(
    () => kioskCartStore.step,
    (step) => {
        if (idleInterval !== null) {
            window.clearInterval(idleInterval);
            idleInterval = null;
        }
        if (step !== 'welcome' && step !== 'confirmation') {
            idleInterval = window.setInterval(() => {
                idleSeconds.value += 1;
                if (idleSeconds.value >= props.settings.idle_timeout_seconds) {
                    resetAllState();
                }
            }, 1000);
        }

        if (confirmationTimer !== null) {
            window.clearInterval(confirmationTimer);
            confirmationTimer = null;
        }
        if (step === 'confirmation') {
            confirmationSecondsLeft.value =
                props.settings.confirmation_reset_seconds;
            confirmationTimer = window.setInterval(() => {
                if (confirmationSecondsLeft.value <= 1) {
                    resetAllState();
                    confirmationSecondsLeft.value =
                        props.settings.confirmation_reset_seconds;
                } else {
                    confirmationSecondsLeft.value -= 1;
                }
            }, 1000);
        }
    },
    { immediate: true },
);

watch(
    () => [kioskCartStore.step, activePayment.value?.expires_at],
    () => {
        if (qrisTimer !== null) {
            window.clearInterval(qrisTimer);
            qrisTimer = null;
        }
        if (
            kioskCartStore.step !== 'payment' ||
            !activePayment.value?.expires_at
        ) {
            return;
        }
        const updateTimer = () => {
            const diff = Math.max(
                0,
                Math.floor(
                    (new Date(activePayment.value!.expires_at!).getTime() -
                        Date.now()) /
                        1000,
                ),
            );
            qrisSecondsLeft.value = diff;
        };
        updateTimer();
        qrisTimer = window.setInterval(updateTimer, 1000);
    },
    { immediate: true },
);

async function refreshAvailability() {
    try {
        const res = await jsonRequest<{
            availability: Record<
                number,
                {
                    is_available: boolean;
                    reason: string | null;
                    max_servings: number | null;
                }
            >;
        }>('/kiosk/availability');

        if (!res.ok) {
            return;
        }

        categories.value = categories.value.map((cat) => ({
            ...cat,
            items: cat.items.map((item) => {
                const avail = res.data.availability[item.id];
                if (!avail) {
                    return item;
                }
                return {
                    ...item,
                    is_available: avail.is_available,
                    unavailable_reason: avail.reason,
                    max_servings: avail.max_servings,
                };
            }),
        }));
    } catch {
        // Silent retry
    }
}

const availabilityEnabled = computed(
    () => kioskCartStore.step === 'menu' || kioskCartStore.step === 'cart',
);
usePolling(refreshAvailability, 5000, availabilityEnabled);

async function pollQrisStatus() {
    if (!activeOrder.value) {
        return;
    }
    try {
        const res = await jsonRequest<{
            order_status: string;
            payment_status: string;
            queue_number: string;
        }>(`/kiosk/orders/${activeOrder.value.id}/qris-status`);

        if (!res.ok) {
            return;
        }

        if (
            res.data.order_status === 'paid' ||
            res.data.payment_status === 'paid'
        ) {
            kioskCartStore.clearCart();
            confirmationStatus.value = 'paid';
            kioskCartStore.setStep('confirmation');
            return;
        }

        if (res.data.payment_status === 'expired' && activePayment.value) {
            activePayment.value = { ...activePayment.value, status: 'expired' };
        }
    } catch {
        // Silent retry
    }
}

const qrisPollingEnabled = computed(
    () =>
        kioskCartStore.step === 'payment' &&
        activeOrder.value !== null &&
        activePayment.value?.status === 'pending',
);
usePolling(pollQrisStatus, 2000, qrisPollingEnabled);

function handleLogoPointerDown() {
    if (longPressTimer !== null) {
        window.clearTimeout(longPressTimer);
    }
    longPressTimer = window.setTimeout(() => {
        pinExitOpen.value = true;
    }, 3000);
}

function handleLogoPointerUp() {
    if (longPressTimer !== null) {
        window.clearTimeout(longPressTimer);
        longPressTimer = null;
    }
}

function handleLogoTap() {
    tapCount += 1;
    if (tapWindowTimer !== null) {
        window.clearTimeout(tapWindowTimer);
    }
    if (tapCount >= 5) {
        tapCount = 0;
        pinExitOpen.value = true;
        return;
    }
    tapWindowTimer = window.setTimeout(() => {
        tapCount = 0;
    }, 3000);
}

function handleStartOrder() {
    handleUserActivity();
    try {
        if (!document.fullscreenElement) {
            void document.documentElement.requestFullscreen?.();
        }
    } catch {
        // Optional
    }
    kioskCartStore.setStep('order_type');
}

const totalCartCount = computed(() =>
    kioskCartStore.items.reduce((sum, item) => sum + item.quantity, 0),
);
const totalCartAmount = computed(() =>
    kioskCartStore.items.reduce(
        (sum, item) => sum + (item.unitPrice + item.modifiersTotal) * item.quantity,
        0,
    ),
);

async function handleCheckout(method: 'qris' | 'cashier') {
    if (
        !kioskCartStore.orderType ||
        kioskCartStore.items.length === 0 ||
        submittingCheckout.value
    ) {
        return;
    }

    submittingCheckout.value = true;
    checkoutError.value = null;

    try {
        const res = await jsonRequest<{
            order?: ActiveOrder;
            payment?: ActivePayment | null;
            message?: string;
            conflicts?: ConflictEntry[];
        }>('/kiosk/checkout', {
            method: 'POST',
            body: {
                order_type: kioskCartStore.orderType,
                customer_name: kioskCartStore.customerName.trim() || null,
                payment_method: method,
                items: kioskCartStore.items.map((item) => ({
                    menu_item_id: item.menuItemId,
                    quantity: item.quantity,
                    notes: item.notes || null,
                    modifier_option_ids: item.selectedModifiers.map(
                        (m) => m.optionId,
                    ),
                })),
            },
        });

        if (res.status === 409 && res.data.conflicts) {
            conflicts.value = res.data.conflicts;
            return;
        }

        if (!res.ok || !res.data.order) {
            checkoutError.value =
                res.data.message ?? 'Gagal memproses pesanan.';
            return;
        }

        activeOrder.value = res.data.order;

        if (method === 'qris' && res.data.payment) {
            activePayment.value = res.data.payment;
            kioskCartStore.setStep('payment');
        } else {
            kioskCartStore.clearCart();
            confirmationStatus.value = 'pending_cashier';
            kioskCartStore.setStep('confirmation');
        }
    } catch {
        checkoutError.value = 'Koneksi bermasalah. Silakan coba lagi.';
    } finally {
        submittingCheckout.value = false;
    }
}

async function handleRegenerateQris() {
    if (!activeOrder.value) return;
    const res = await jsonRequest<{ payment?: ActivePayment }>(
        `/kiosk/orders/${activeOrder.value.id}/qris-regenerate`,
        { method: 'POST' },
    );
    if (res.ok && res.data.payment) {
        activePayment.value = res.data.payment;
    }
}

async function handleSimulateQrisPaid() {
    if (!activeOrder.value) return;
    const res = await jsonRequest<{ order_status?: string }>(
        `/kiosk/orders/${activeOrder.value.id}/qris-simulate-paid`,
        { method: 'POST' },
    );
    if (res.ok && res.data.order_status === 'paid') {
        kioskCartStore.clearCart();
        confirmationStatus.value = 'paid';
        kioskCartStore.setStep('confirmation');
    }
}

function handleFallbackCashier() {
    if (!activeOrder.value) return;
    kioskCartStore.clearCart();
    confirmationStatus.value = 'pending_cashier';
    kioskCartStore.setStep('confirmation');
}

const activeCategory = computed(
    () =>
        categories.value.find((cat) => cat.id === activeCategoryId.value) ??
        categories.value[0],
);

const showIdleWarning = computed(
    () =>
        kioskCartStore.step !== 'welcome' &&
        kioskCartStore.step !== 'confirmation' &&
        idleSeconds.value >= props.settings.idle_warning_seconds,
);

function findMenuById(menuId: number): KioskMenuItem | undefined {
    return categories.value.flatMap((c) => c.items).find((m) => m.id === menuId);
}
</script>

<template>
    <Head :title="`Kiosk — ${settings.cafe_name}`" />

    <div
        class="min-h-screen w-full touch-manipulation overflow-x-hidden bg-background text-foreground select-none"
        @contextmenu.prevent
        @pointerdown="handleUserActivity"
        @keydown="handleUserActivity"
    >
        <div
            v-if="kioskCartStore.step === 'welcome'"
            class="flex min-h-screen flex-col items-center justify-between bg-espresso px-6 py-12 text-oat"
        >
            <div class="flex w-full max-w-2xl items-center justify-between text-xs text-latte/70">
                <span>{{ device.name }}</span>
                <span>Self-Ordering Kiosk</span>
            </div>

            <div class="flex max-w-xl flex-col items-center text-center">
                <button
                    type="button"
                    @click="handleLogoTap"
                    @pointerdown="handleLogoPointerDown"
                    @pointerup="handleLogoPointerUp"
                    @pointerleave="handleLogoPointerUp"
                    class="mb-8 flex size-28 items-center justify-center rounded-3xl bg-crema text-white shadow-xl"
                    aria-label="Logo Our Coffee"
                >
                    <Coffee class="size-14" />
                </button>

                <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl">
                    {{ settings.cafe_name }}
                </h1>
                <p class="mt-4 text-lg text-latte/85">
                    Pesan kopi favorit Anda dengan cepat tanpa antre di kasir.
                </p>

                <Button
                    @click="handleStartOrder"
                    class="mt-10 min-h-20 min-w-72 rounded-2xl bg-crema px-10 text-2xl font-extrabold text-white shadow-lg hover:bg-crema/90"
                >
                    Mulai Pesan
                </Button>
            </div>

            <p class="text-xs font-medium text-latte/80">
                Ketuk tombol di atas untuk memulai pesanan Anda
            </p>
        </div>

        <div
            v-else-if="kioskCartStore.step === 'order_type'"
            class="mx-auto flex min-h-screen max-w-3xl flex-col justify-center gap-8 px-6 py-10"
        >
            <div class="text-center">
                <h1 class="text-3xl font-extrabold tracking-tight">
                    Mau nikmati di mana?
                </h1>
                <p class="mt-2 text-base text-muted-foreground">
                    Pilih tipe pesanan Anda terlebih dahulu
                </p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <button
                    type="button"
                    @click="
                        kioskCartStore.setOrderType('dine_in');
                        kioskCartStore.setStep('menu');
                    "
                    :class="
                        cn(
                            'flex min-h-48 flex-col items-center justify-center gap-4 rounded-3xl border-2 bg-card p-8 shadow-sm transition-all hover:border-primary',
                            kioskCartStore.orderType === 'dine_in' &&
                                'border-primary bg-primary/5',
                        )
                    "
                >
                    <div class="flex size-16 items-center justify-center rounded-2xl bg-secondary text-secondary-foreground">
                        <Store class="size-9" />
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-extrabold">Dine-In</p>
                        <p class="text-sm text-muted-foreground">
                            Minum di tempat
                        </p>
                    </div>
                </button>

                <button
                    type="button"
                    @click="
                        kioskCartStore.setOrderType('takeaway');
                        kioskCartStore.setStep('menu');
                    "
                    :class="
                        cn(
                            'flex min-h-48 flex-col items-center justify-center gap-4 rounded-3xl border-2 bg-card p-8 shadow-sm transition-all hover:border-primary',
                            kioskCartStore.orderType === 'takeaway' &&
                                'border-primary bg-primary/5',
                        )
                    "
                >
                    <div class="flex size-16 items-center justify-center rounded-2xl bg-secondary text-secondary-foreground">
                        <ShoppingBag class="size-9" />
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-extrabold">Takeaway</p>
                        <p class="text-sm text-muted-foreground">Bawa pulang</p>
                    </div>
                </button>
            </div>

            <div class="mx-auto w-full max-w-md space-y-2 rounded-2xl border bg-card p-5">
                <Label for="kiosk-nickname">
                    Nama Panggilan untuk Antrean (Opsional)
                </Label>
                <Input
                    id="kiosk-nickname"
                    :model-value="kioskCartStore.customerName"
                    @update:model-value="kioskCartStore.setCustomerName($event)"
                    maxlength="80"
                    placeholder="Contoh: Rafi"
                    class="h-14 text-base"
                />
            </div>

            <div class="flex justify-center">
                <Button
                    variant="outline"
                    @click="resetAllState"
                    class="min-h-16 min-w-44 rounded-2xl text-base"
                >
                    Kembali
                </Button>
            </div>
        </div>

        <div
            v-else-if="kioskCartStore.step === 'menu'"
            class="flex h-screen w-full flex-col overflow-hidden"
        >
            <header class="flex items-center justify-between border-b bg-card px-5 py-3">
                <div class="flex items-center gap-3">
                    <Button
                        variant="outline"
                        @click="kioskCartStore.setStep('order_type')"
                        class="min-h-16 min-w-16 rounded-2xl"
                        aria-label="Ubah tipe pesanan"
                    >
                        <ArrowLeft class="size-6" />
                    </Button>
                    <div>
                        <h1 class="text-lg font-extrabold">
                            {{ settings.cafe_name }}
                        </h1>
                        <p class="text-xs text-muted-foreground">
                            {{
                                kioskCartStore.orderType === 'dine_in'
                                    ? 'Dine-In'
                                    : 'Takeaway'
                            }}{{
                                kioskCartStore.customerName
                                    ? ` · ${kioskCartStore.customerName}`
                                    : ''
                            }}
                        </p>
                    </div>
                </div>

                <Button
                    @click="kioskCartStore.setStep('cart')"
                    class="min-h-16 gap-3 rounded-2xl px-6 text-base font-bold"
                >
                    <ShoppingBag class="size-5" />
                    <span>Keranjang ({{ totalCartCount }})</span>
                    <span class="font-money">
                        {{ formatRupiah(totalCartAmount) }}
                    </span>
                </Button>
            </header>

            <div class="flex min-h-0 flex-1 overflow-hidden">
                <aside
                    aria-label="Kategori Menu"
                    class="flex w-28 shrink-0 flex-col gap-2 overflow-y-auto bg-espresso p-2.5 text-oat sm:w-40"
                >
                    <button
                        v-for="cat in categories"
                        :key="cat.id"
                        type="button"
                        @click="activeCategoryId = cat.id"
                        :class="
                            cn(
                                'flex min-h-20 flex-col items-center justify-center gap-1.5 rounded-2xl px-2 py-3 text-center text-xs font-bold transition-colors sm:text-sm',
                                cat.id === activeCategory?.id
                                    ? 'bg-crema text-white shadow-sm'
                                    : 'text-latte/80 hover:bg-white/10',
                            )
                        "
                    >
                        <component
                            :is="categoryIconComponent(cat.icon)"
                            class="size-6 shrink-0"
                        />
                        <span class="line-clamp-2">{{ cat.name }}</span>
                    </button>
                </aside>

                <section class="flex-1 overflow-y-auto p-5 pb-28">
                    <h2 class="mb-4 text-xl font-extrabold">
                        {{ activeCategory?.name }}
                    </h2>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <button
                            v-for="item in activeCategory?.items ?? []"
                            :key="item.id"
                            type="button"
                            :disabled="!item.is_available"
                            @click="
                                editingCartItem = null;
                                sheetMenu = item;
                            "
                            :class="
                                cn(
                                    'flex flex-col overflow-hidden rounded-2xl border bg-card text-left shadow-xs transition-all',
                                    item.is_available
                                        ? 'hover:border-primary hover:shadow-md'
                                        : 'cursor-not-allowed opacity-55 grayscale',
                                )
                            "
                        >
                            <div class="relative aspect-[4/3] w-full bg-secondary">
                                <img
                                    v-if="item.image_url"
                                    :src="item.image_url"
                                    :alt="item.name"
                                    class="size-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex size-full items-center justify-center text-muted-foreground"
                                >
                                    <Coffee class="size-12 opacity-40" />
                                </div>

                                <Badge
                                    v-if="item.badge === 'best_seller'"
                                    class="absolute top-2.5 left-2.5 bg-crema text-white"
                                >
                                    Best Seller
                                </Badge>
                                <Badge
                                    v-if="item.badge === 'new'"
                                    class="absolute top-2.5 left-2.5 bg-espresso text-oat"
                                >
                                    New
                                </Badge>
                                <div
                                    v-if="!item.is_available"
                                    class="absolute inset-0 flex items-center justify-center bg-black/50"
                                >
                                    <span class="rounded-xl bg-red-700 px-4 py-1.5 text-sm font-extrabold tracking-wider text-white">
                                        HABIS
                                    </span>
                                </div>
                            </div>

                            <div class="flex flex-1 flex-col justify-between p-4">
                                <div>
                                    <p class="text-base font-bold">{{ item.name }}</p>
                                    <p
                                        v-if="item.description"
                                        class="mt-1 line-clamp-2 text-xs text-muted-foreground"
                                    >
                                        {{ item.description }}
                                    </p>
                                </div>
                                <p class="font-money mt-3 text-base font-extrabold text-primary">
                                    {{ formatRupiah(item.price) }}
                                </p>
                            </div>
                        </button>
                    </div>
                </section>
            </div>

            <div
                v-if="totalCartCount > 0"
                class="fixed inset-x-0 bottom-0 border-t bg-card/95 p-4 backdrop-blur-xs"
            >
                <div class="mx-auto flex max-w-4xl items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-medium text-muted-foreground">
                            Total Keranjang ({{ totalCartCount }} item)
                        </p>
                        <p class="font-money text-xl font-extrabold text-primary">
                            {{ formatRupiah(totalCartAmount) }}
                        </p>
                    </div>
                    <Button
                        @click="kioskCartStore.setStep('cart')"
                        class="min-h-16 min-w-48 rounded-2xl px-8 text-lg font-extrabold"
                    >
                        Lihat Keranjang
                    </Button>
                </div>
            </div>
        </div>

        <div
            v-else-if="kioskCartStore.step === 'cart'"
            class="mx-auto flex min-h-screen max-w-3xl flex-col gap-6 px-6 py-8"
        >
            <div class="flex items-center gap-4">
                <Button
                    variant="outline"
                    @click="kioskCartStore.setStep('menu')"
                    class="min-h-16 min-w-16 rounded-2xl"
                    aria-label="Kembali ke menu"
                >
                    <ArrowLeft class="size-6" />
                </Button>
                <div>
                    <h1 class="text-2xl font-extrabold">Review Keranjang</h1>
                    <p class="text-sm text-muted-foreground">
                        {{
                            kioskCartStore.orderType === 'dine_in'
                                ? 'Dine-In'
                                : 'Takeaway'
                        }}{{
                            kioskCartStore.customerName
                                ? ` · ${kioskCartStore.customerName}`
                                : ''
                        }}
                    </p>
                </div>
            </div>

            <div
                v-if="checkoutError"
                class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800"
            >
                {{ checkoutError }}
            </div>

            <div
                v-if="kioskCartStore.items.length === 0"
                class="flex flex-1 flex-col items-center justify-center rounded-3xl border bg-card p-10 text-center"
            >
                <ShoppingBag class="size-12 text-muted-foreground/50" />
                <p class="mt-3 text-lg font-bold">Keranjang masih kosong</p>
                <Button
                    @click="kioskCartStore.setStep('menu')"
                    class="mt-6 min-h-16 rounded-2xl px-8 text-base font-bold"
                >
                    Pilih Menu
                </Button>
            </div>

            <template v-else>
                <div class="space-y-3">
                    <div
                        v-for="item in kioskCartStore.items"
                        :key="item.lineId"
                        class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border bg-card p-4 shadow-xs"
                    >
                        <div class="min-w-48 flex-1">
                            <p class="text-base font-bold">{{ item.name }}</p>
                            <p
                                v-if="item.selectedModifiers.length > 0"
                                class="mt-1 text-xs text-muted-foreground"
                            >
                                {{
                                    item.selectedModifiers
                                        .map((m) => m.optionName)
                                        .join(', ')
                                }}
                            </p>
                            <p
                                v-if="item.notes"
                                class="mt-1 text-xs font-medium text-primary"
                            >
                                Catatan: {{ item.notes }}
                            </p>
                            <p class="font-money mt-2 text-sm font-bold text-primary">
                                {{
                                    formatRupiah(
                                        (item.unitPrice + item.modifiersTotal) *
                                            item.quantity,
                                    )
                                }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <Button
                                v-if="findMenuById(item.menuItemId)"
                                variant="outline"
                                @click="
                                    editingCartItem = item;
                                    sheetMenu = findMenuById(item.menuItemId) ?? null;
                                "
                                class="min-h-16 min-w-16 rounded-xl"
                                aria-label="Edit kustomisasi"
                            >
                                <Edit2 class="size-5" />
                            </Button>
                            <Button
                                variant="outline"
                                @click="kioskCartStore.updateQuantity(item.lineId, -1)"
                                class="min-h-16 min-w-16 rounded-xl"
                                aria-label="Kurangi"
                            >
                                <Minus class="size-5" />
                            </Button>
                            <span class="font-money min-w-8 text-center text-lg font-bold">
                                {{ item.quantity }}
                            </span>
                            <Button
                                variant="outline"
                                @click="kioskCartStore.updateQuantity(item.lineId, 1)"
                                class="min-h-16 min-w-16 rounded-xl"
                                aria-label="Tambah"
                            >
                                <Plus class="size-5" />
                            </Button>
                            <Button
                                variant="outline"
                                @click="kioskCartStore.removeItem(item.lineId)"
                                class="min-h-16 min-w-16 rounded-xl"
                                aria-label="Hapus item"
                            >
                                <Trash2 class="size-5 text-destructive" />
                            </Button>
                        </div>
                    </div>
                </div>

                <div class="mt-auto space-y-4 rounded-3xl border bg-card p-6 shadow-xs">
                    <div class="flex items-center justify-between text-lg font-bold">
                        <span>Total Pembayaran</span>
                        <span class="font-money text-2xl text-primary">
                            {{ formatRupiah(totalCartAmount) }}
                        </span>
                    </div>

                    <div class="grid gap-3">
                        <Button
                            :disabled="submittingCheckout"
                            @click="handleCheckout('cashier')"
                            class="min-h-16 gap-2 rounded-2xl text-base font-extrabold"
                        >
                            <Banknote class="size-5" />
                            Bayar Tunai di Kasir
                        </Button>
                    </div>
                </div>
            </template>
        </div>

        <div
            v-else-if="kioskCartStore.step === 'payment' && activeOrder && activePayment"
            class="mx-auto flex min-h-screen max-w-xl flex-col items-center justify-center gap-6 px-6 py-10 text-center"
        >
            <div class="w-full rounded-3xl border bg-card p-8 shadow-sm">
                <p class="text-sm font-semibold text-muted-foreground">
                    Scan QRIS untuk Melunasi Pesanan
                </p>
                <p class="font-money mt-1 text-3xl font-extrabold text-primary">
                    {{ formatRupiah(activeOrder.total_amount) }}
                </p>
                <p class="font-money mt-1 text-xs text-muted-foreground">
                    Antrean #{{ activeOrder.queue_number }} ·
                    {{ activeOrder.order_number }}
                </p>

                <div v-if="activePayment.qris_payload" class="my-6 flex justify-center">
                    <svg
                        viewBox="0 0 21 21"
                        class="size-56 rounded-2xl border-8 border-white bg-white shadow-md"
                        role="img"
                        aria-label="Kode QRIS Pembayaran"
                    >
                        <template v-for="(row, r) in qrCells" :key="r">
                            <template v-for="(filled, c) in row" :key="`${r}-${c}`">
                                <rect
                                    v-if="filled"
                                    :x="c"
                                    :y="r"
                                    width="1"
                                    height="1"
                                    fill="#1c120d"
                                />
                            </template>
                        </template>
                    </svg>
                </div>

                <div
                    v-if="activePayment.status === 'expired' || qrisSecondsLeft === 0"
                    class="space-y-3"
                >
                    <p class="text-sm font-bold text-red-700">
                        Kode QRIS sudah kedaluwarsa.
                    </p>
                    <Button
                        @click="handleRegenerateQris"
                        class="min-h-16 w-full gap-2 rounded-2xl text-base font-bold"
                    >
                        <RefreshCw class="size-5" />
                        Buat QR Baru
                    </Button>
                </div>
                <p
                    v-else
                    class="font-money text-sm font-semibold text-muted-foreground"
                >
                    Berlaku selama {{ Math.floor(qrisSecondsLeft / 60) }}:{{
                        String(qrisSecondsLeft % 60).padStart(2, '0')
                    }}
                </p>

                <div class="mt-6 space-y-3">
                    <Button
                        v-if="isLocalDemo && activePayment.status === 'pending'"
                        variant="outline"
                        @click="handleSimulateQrisPaid"
                        class="min-h-16 w-full rounded-2xl border-emerald-600 text-base font-bold text-emerald-800 hover:bg-emerald-50"
                    >
                        Simulasikan QRIS Lunas (Demo)
                    </Button>

                    <Button
                        variant="secondary"
                        @click="handleFallbackCashier"
                        class="min-h-16 w-full rounded-2xl text-base font-bold"
                    >
                        Bayar di Kasir Saja
                    </Button>
                </div>
            </div>
        </div>

        <div
            v-else-if="kioskCartStore.step === 'confirmation' && activeOrder"
            class="flex min-h-screen flex-col items-center justify-center bg-espresso px-6 py-12 text-center text-oat"
        >
            <div class="max-w-lg rounded-3xl border border-white/15 bg-white/5 p-10">
                <CheckCircle2 class="mx-auto size-16 text-emerald-400" />
                <h1 class="mt-4 text-3xl font-extrabold">
                    {{
                        confirmationStatus === 'paid'
                            ? 'Pembayaran Berhasil!'
                            : 'Silakan Bayar di Kasir'
                    }}
                </h1>
                <p class="mt-2 text-base text-latte/85">
                    {{
                        confirmationStatus === 'paid'
                            ? 'Pesanan Anda langsung diproses oleh Barista kami.'
                            : 'Tunjukkan nomor antrean di bawah ini ke meja kasir untuk pelunasan.'
                    }}
                </p>

                <div class="my-8 rounded-2xl bg-crema px-6 py-6 text-white">
                    <p class="text-xs font-semibold tracking-widest text-white/80 uppercase">
                        Nomor Antrean
                    </p>
                    <p class="font-money mt-1 text-6xl font-extrabold">
                        #{{ activeOrder.queue_number }}
                    </p>
                </div>

                <p class="font-money text-sm text-latte/75">
                    Layar akan kembali ke halaman utama dalam
                    {{ confirmationSecondsLeft }} detik...
                </p>

                <Button
                    variant="secondary"
                    @click="resetAllState"
                    class="mt-6 min-h-16 min-w-48 rounded-2xl text-base font-bold"
                >
                    Pesan Baru
                </Button>
            </div>
        </div>
    </div>

    <ModifierSheet
        :menu-item="sheetMenu"
        :editing-cart-item="editingCartItem"
        :open="sheetMenu !== null"
        @close="
            sheetMenu = null;
            editingCartItem = null;
        "
        @confirm="kioskCartStore.addOrUpdateItem($event)"
    />

    <Dialog :open="conflicts.length > 0" @close="conflicts = []">
        <div class="mb-4">
            <h2 class="flex items-center gap-2 text-lg font-bold text-red-700">
                <AlertTriangle class="size-5" />
                Stok Item Baru Saja Habis
            </h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Item berikut tidak dapat diproses karena stok bahan baku baru saja habis:
            </p>
        </div>
        <ul class="space-y-2 text-sm">
            <li
                v-for="(c, idx) in conflicts"
                :key="`${c.menu_item_id}-${idx}`"
                class="rounded-xl border border-red-200 bg-red-50 px-3 py-2"
            >
                <p class="font-bold text-red-900">{{ c.menu_item_name }}</p>
                <p class="text-xs text-red-800">{{ c.reason }}</p>
            </li>
        </ul>
        <div class="mt-6 flex justify-end gap-2">
            <Button variant="outline" @click="conflicts = []" class="min-h-14">
                Kembali
            </Button>
            <Button
                @click="
                    kioskCartStore.removeItemsByMenuIds(
                        conflicts.map((c) => c.menu_item_id),
                    );
                    conflicts = [];
                    refreshAvailability();
                "
                class="min-h-14 font-bold"
            >
                Hapus Item Habis & Lanjutkan
            </Button>
        </div>
    </Dialog>

    <Dialog :open="showIdleWarning" :show-close="false" @close="handleUserActivity">
        <div class="mb-4">
            <h2 class="text-lg font-bold">Apakah Anda Masih di Sini?</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Sesi pemesanan akan otomatis direset dalam
                <span class="font-money font-bold">
                    {{ Math.max(0, settings.idle_timeout_seconds - idleSeconds) }}
                </span>
                detik.
            </p>
        </div>
        <div class="mt-6 flex justify-end gap-2">
            <Button variant="outline" @click="resetAllState" class="min-h-14">
                Mulai Ulang
            </Button>
            <Button @click="handleUserActivity" class="min-h-14 font-bold">
                Lanjutkan Pesanan
            </Button>
        </div>
    </Dialog>

    <PinExitModal :open="pinExitOpen" @close="pinExitOpen = false" />
</template>
