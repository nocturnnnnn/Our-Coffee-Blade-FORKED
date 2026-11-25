<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Receipt,
    ShoppingBag,
    TrendingUp,
    Wallet,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import { usePolling } from '@/composables/usePolling';
import { formatRupiah } from '@/lib/format';
import { jsonRequest } from '@/lib/http';
import { cn } from '@/lib/utils';

type DashboardProps = {
    days: number;
    kpis: {
        today_revenue: number;
        today_orders: number;
        today_aov: number;
        low_stock_count: number;
    };
    revenueTrend: Array<{
        date: string;
        label: string;
        revenue: number;
        orders: number;
    }>;
    channelSplit: {
        kiosk_orders: number;
        kiosk_revenue: number;
        pos_orders: number;
        pos_revenue: number;
    };
    peakHours: Array<{
        hour: number;
        label: string;
        orders: number;
    }>;
    topMenus: Array<{
        menu_item_id: number;
        name: string;
        qty_sold: number;
        revenue: number;
        cogs: number;
        gross_profit: number;
        margin_percent: number;
    }>;
    lowStockIngredients: Array<{
        id: number;
        name: string;
        unit: string;
        current_stock: number;
        min_stock: number;
    }>;
};

const props = defineProps<DashboardProps>();
const data = ref<DashboardProps>({ ...props });

async function refreshMetrics() {
    try {
        const res = await jsonRequest<DashboardProps>(
            `/admin/dashboard/metrics?days=${data.value.days}`,
        );
        if (res.ok) {
            data.value = res.data;
        }
    } catch {
        // Silent retry
    }
}

usePolling(refreshMetrics, 30000, true);

function switchDays(days: 7 | 30) {
    router.get('/admin/dashboard', { days }, { preserveState: false });
}

const maxRevenue = computed(() =>
    Math.max(1, ...data.value.revenueTrend.map((d) => d.revenue)),
);
const maxPeak = computed(() =>
    Math.max(1, ...data.value.peakHours.map((h) => h.orders)),
);

const totalChannelOrders = computed(
    () =>
        data.value.channelSplit.kiosk_orders + data.value.channelSplit.pos_orders,
);
const kioskPercent = computed(() =>
    totalChannelOrders.value > 0
        ? Math.round(
              (data.value.channelSplit.kiosk_orders / totalChannelOrders.value) *
                  100,
          )
        : 0,
);
const posPercent = computed(() =>
    totalChannelOrders.value > 0 ? 100 - kioskPercent.value : 0,
);
const donutRadius = 42;
const donutCircumference = 2 * Math.PI * donutRadius;
const kioskDash = computed(
    () => (kioskPercent.value / 100) * donutCircumference,
);
</script>

<template>
    <Head title="Dashboard Admin" />
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    Dashboard Operasional
                </h1>
                <p class="text-sm text-muted-foreground">
                    Ringkasan KPI real-time (auto-refresh setiap 30 detik).
                </p>
            </div>
            <div class="flex items-center gap-2">
                <Button
                    size="sm"
                    :variant="data.days === 7 ? 'default' : 'outline'"
                    @click="switchDays(7)"
                >
                    7 Hari
                </Button>
                <Button
                    size="sm"
                    :variant="data.days === 30 ? 'default' : 'outline'"
                    @click="switchDays(30)"
                >
                    30 Hari
                </Button>
            </div>
        </div>

        <!-- 4 KPI Cards -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border bg-card p-5 shadow-xs">
                <div class="flex items-center justify-between text-xs font-medium text-muted-foreground">
                    <span>Omzet Hari Ini</span>
                    <Wallet class="size-4 text-primary" />
                </div>
                <p class="font-money mt-2 text-2xl font-extrabold text-primary">
                    {{ formatRupiah(data.kpis.today_revenue) }}
                </p>
            </div>

            <div class="rounded-2xl border bg-card p-5 shadow-xs">
                <div class="flex items-center justify-between text-xs font-medium text-muted-foreground">
                    <span>Total Transaksi</span>
                    <Receipt class="size-4 text-primary" />
                </div>
                <p class="font-money mt-2 text-2xl font-extrabold">
                    {{ data.kpis.today_orders }}
                </p>
            </div>

            <div class="rounded-2xl border bg-card p-5 shadow-xs">
                <div class="flex items-center justify-between text-xs font-medium text-muted-foreground">
                    <span>Average Order Value</span>
                    <TrendingUp class="size-4 text-emerald-700" />
                </div>
                <p class="font-money mt-2 text-2xl font-extrabold">
                    {{ formatRupiah(data.kpis.today_aov) }}
                </p>
            </div>

            <div class="rounded-2xl border bg-card p-5 shadow-xs">
                <div class="flex items-center justify-between text-xs font-medium text-muted-foreground">
                    <span>Bahan Stok Menipis</span>
                    <AlertTriangle
                        :class="
                            cn(
                                'size-4',
                                data.kpis.low_stock_count > 0
                                    ? 'text-red-700'
                                    : 'text-emerald-700',
                            )
                        "
                    />
                </div>
                <p
                    :class="
                        cn(
                            'font-money mt-2 text-2xl font-extrabold',
                            data.kpis.low_stock_count > 0 && 'text-red-700',
                        )
                    "
                >
                    {{ data.kpis.low_stock_count }}
                </p>
            </div>
        </div>

        <!-- Revenue Trend + Channel Donut -->
        <div class="grid gap-5 lg:grid-cols-3">
            <div class="rounded-2xl border bg-card p-5 shadow-xs lg:col-span-2">
                <h2 class="text-base font-bold">
                    Tren Omzet ({{ data.days }} Hari Terakhir)
                </h2>
                <div class="mt-4 flex h-52 items-end gap-1.5 overflow-x-auto pt-4">
                    <div
                        v-for="point in data.revenueTrend"
                        :key="point.date"
                        class="flex min-w-5 flex-1 flex-col items-center gap-1.5"
                        :title="`${point.date}: ${formatRupiah(point.revenue)} (${point.orders} order)`"
                    >
                        <div class="flex h-40 w-full items-end">
                            <div
                                class="w-full rounded-t-md bg-crema/85 transition-all hover:bg-crema"
                                :style="{
                                    height: `${Math.max(4, Math.round((point.revenue / maxRevenue) * 100))}%`,
                                }"
                            />
                        </div>
                        <span class="font-money text-[10px] text-muted-foreground">
                            {{ point.label }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col justify-between rounded-2xl border bg-card p-5 shadow-xs">
                <h2 class="text-base font-bold">Kontribusi Channel</h2>
                <div class="my-4 flex items-center justify-center">
                    <svg
                        viewBox="0 0 120 120"
                        class="size-40 -rotate-90"
                        role="img"
                        aria-label="Donut Chart Kiosk vs POS"
                    >
                        <circle
                            cx="60"
                            cy="60"
                            :r="donutRadius"
                            fill="transparent"
                            stroke="#1c120d"
                            stroke-width="16"
                        />
                        <circle
                            v-if="totalChannelOrders > 0"
                            cx="60"
                            cy="60"
                            :r="donutRadius"
                            fill="transparent"
                            stroke="#b45309"
                            stroke-width="16"
                            :stroke-dasharray="`${kioskDash} ${donutCircumference}`"
                        />
                    </svg>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-2 font-semibold">
                            <span class="size-3 rounded-full bg-crema" />
                            Self-Ordering Kiosk ({{ kioskPercent }}%)
                        </span>
                        <span class="font-money font-bold">
                            {{ formatRupiah(data.channelSplit.kiosk_revenue) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-2 font-semibold">
                            <span class="size-3 rounded-full bg-espresso" />
                            POS Kasir ({{ posPercent }}%)
                        </span>
                        <span class="font-money font-bold">
                            {{ formatRupiah(data.channelSplit.pos_revenue) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Peak Hours Heatmap -->
        <div class="rounded-2xl border bg-card p-5 shadow-xs">
            <h2 class="text-base font-bold">Heatmap Jam Sibuk (Peak Hours)</h2>
            <div class="mt-4 grid grid-cols-6 gap-2 sm:grid-cols-12">
                <div
                    v-for="bucket in data.peakHours"
                    :key="bucket.hour"
                    :class="
                        cn(
                            'rounded-xl p-2 text-center',
                            bucket.orders === 0
                                ? 'bg-muted text-muted-foreground'
                                : bucket.orders / maxPeak >= 0.75
                                  ? 'bg-crema text-white'
                                  : bucket.orders / maxPeak >= 0.4
                                    ? 'bg-amber-400 font-bold text-stone-950'
                                    : 'bg-secondary font-medium text-secondary-foreground',
                        )
                    "
                >
                    <p class="font-money text-[11px] font-semibold">
                        {{ bucket.label }}
                    </p>
                    <p class="font-money text-sm font-extrabold">
                        {{ bucket.orders }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Top 5 Menus + Low Stock Widget -->
        <div class="grid gap-5 lg:grid-cols-3">
            <div class="overflow-hidden rounded-2xl border bg-card shadow-xs lg:col-span-2">
                <div class="flex items-center justify-between border-b px-5 py-3.5">
                    <h2 class="flex items-center gap-2 text-base font-bold">
                        <ShoppingBag class="size-4 text-primary" />
                        Top 5 Menu & Margin
                    </h2>
                </div>
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-muted/60 text-xs text-muted-foreground uppercase">
                        <tr>
                            <th class="px-4 py-2.5">Menu</th>
                            <th class="px-4 py-2.5">Terjual</th>
                            <th class="px-4 py-2.5">Omzet</th>
                            <th class="px-4 py-2.5">Margin</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="menu in data.topMenus" :key="menu.menu_item_id">
                            <td class="px-4 py-2.5 font-semibold">{{ menu.name }}</td>
                            <td class="font-money px-4 py-2.5">{{ menu.qty_sold }}</td>
                            <td class="font-money px-4 py-2.5 font-semibold">
                                {{ formatRupiah(menu.revenue) }}
                            </td>
                            <td class="font-money px-4 py-2.5 font-bold text-emerald-700">
                                {{ menu.margin_percent }}%
                            </td>
                        </tr>
                        <tr v-if="data.topMenus.length === 0">
                            <td colspan="4" class="px-4 py-6 text-center text-muted-foreground">
                                Belum ada transaksi pada rentang ini.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="rounded-2xl border bg-card p-5 shadow-xs">
                <div class="flex items-center justify-between border-b pb-3">
                    <h2 class="text-base font-bold">Peringatan Stok Menipis</h2>
                    <Link
                        href="/admin/inventory"
                        class="text-xs font-bold text-primary hover:underline"
                    >
                        Kelola Stok
                    </Link>
                </div>
                <div class="mt-3 space-y-2.5">
                    <div
                        v-for="ing in data.lowStockIngredients"
                        :key="ing.id"
                        class="flex items-center justify-between rounded-xl border border-amber-200 bg-amber-50/70 px-3 py-2 text-xs"
                    >
                        <span class="font-semibold text-foreground">{{ ing.name }}</span>
                        <Badge variant="destructive">
                            <span class="font-money">
                                {{ ing.current_stock }} / {{ ing.min_stock }} {{ ing.unit }}
                            </span>
                        </Badge>
                    </div>
                    <p
                        v-if="data.lowStockIngredients.length === 0"
                        class="py-6 text-center text-xs text-muted-foreground"
                    >
                        Seluruh stok bahan baku dalam batas aman.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
