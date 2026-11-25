<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Download, Filter } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import { formatRupiah } from '@/lib/format';

type ReportProps = {
    filters: {
        start_date: string;
        end_date: string;
    };
    summary: {
        gross_sales: number;
        total_discount: number;
        net_revenue: number;
        total_cogs: number;
        gross_profit: number;
        gross_margin_percent: number;
        orders_count: number;
        voided_count: number;
    };
    dailyRows: Array<{
        date: string;
        orders: number;
        net_revenue: number;
        cogs: number;
        profit: number;
    }>;
    menuRows: Array<{
        menu_item_id: number;
        name: string;
        qty_sold: number;
        revenue: number;
        cogs: number;
        gross_profit: number;
        margin_percent: number;
    }>;
};

const props = defineProps<ReportProps>();

const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);

function applyFilter() {
    router.get(
        '/admin/reports',
        { start_date: startDate.value, end_date: endDate.value },
        { preserveState: true },
    );
}

const csvUrl = computed(
    () =>
        `/admin/reports/export?start_date=${encodeURIComponent(startDate.value)}&end_date=${encodeURIComponent(endDate.value)}`,
);
</script>

<template>
    <Head title="Laporan Penjualan & Margin" />
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    Laporan Penjualan & Profitabilitas
                </h1>
                <p class="text-sm text-muted-foreground">
                    Analisa omzet bersih, HPP (BOM), laba kotor, serta ekspor CSV.
                </p>
            </div>

            <a :href="csvUrl">
                <Button class="min-h-11 gap-2">
                    <Download class="size-4" />
                    Ekspor CSV
                </Button>
            </a>
        </div>

        <form
            @submit.prevent="applyFilter"
            class="flex flex-wrap items-end gap-3 rounded-2xl border bg-card p-4 shadow-xs"
        >
            <div class="space-y-1">
                <Label for="rep-start">Dari Tanggal</Label>
                <Input id="rep-start" type="date" v-model="startDate" />
            </div>
            <div class="space-y-1">
                <Label for="rep-end">Sampai Tanggal</Label>
                <Input id="rep-end" type="date" v-model="endDate" />
            </div>
            <Button type="submit" variant="outline" class="gap-2">
                <Filter class="size-4" />
                Terapkan
            </Button>
        </form>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border bg-card p-5 shadow-xs">
                <p class="text-xs text-muted-foreground">Omzet Bersih</p>
                <p class="font-money mt-1.5 text-xl font-extrabold text-primary">
                    {{ formatRupiah(summary.net_revenue) }}
                </p>
                <p class="font-money mt-1 text-xs text-muted-foreground">
                    {{ summary.orders_count }} transaksi ({{ summary.voided_count }} void)
                </p>
            </div>
            <div class="rounded-2xl border bg-card p-5 shadow-xs">
                <p class="text-xs text-muted-foreground">Total HPP (COGS)</p>
                <p class="font-money mt-1.5 text-xl font-extrabold">
                    {{ formatRupiah(summary.total_cogs) }}
                </p>
                <p class="font-money mt-1 text-xs text-muted-foreground">
                    Diskon: {{ formatRupiah(summary.total_discount) }}
                </p>
            </div>
            <div class="rounded-2xl border bg-card p-5 shadow-xs">
                <p class="text-xs text-muted-foreground">Laba Kotor</p>
                <p class="font-money mt-1.5 text-xl font-extrabold text-emerald-700">
                    {{ formatRupiah(summary.gross_profit) }}
                </p>
            </div>
            <div class="rounded-2xl border bg-card p-5 shadow-xs">
                <p class="text-xs text-muted-foreground">Gross Margin</p>
                <p class="font-money mt-1.5 text-xl font-extrabold text-emerald-700">
                    {{ summary.gross_margin_percent }}%
                </p>
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <div class="overflow-hidden rounded-2xl border bg-card shadow-xs">
                <div class="border-b px-5 py-3.5">
                    <h2 class="text-base font-bold">Rekap Harian</h2>
                </div>
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-muted/60 text-xs text-muted-foreground uppercase">
                        <tr>
                            <th class="px-4 py-2.5">Tanggal</th>
                            <th class="px-4 py-2.5">Order</th>
                            <th class="px-4 py-2.5">Omzet</th>
                            <th class="px-4 py-2.5">Laba Kotor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="row in dailyRows" :key="row.date">
                            <td class="font-money px-4 py-2.5">{{ row.date }}</td>
                            <td class="font-money px-4 py-2.5">{{ row.orders }}</td>
                            <td class="font-money px-4 py-2.5 font-semibold">
                                {{ formatRupiah(row.net_revenue) }}
                            </td>
                            <td class="font-money px-4 py-2.5 font-semibold text-emerald-700">
                                {{ formatRupiah(row.profit) }}
                            </td>
                        </tr>
                        <tr v-if="dailyRows.length === 0">
                            <td colspan="4" class="px-4 py-6 text-center text-muted-foreground">
                                Tidak ada data pada rentang tanggal ini.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="overflow-hidden rounded-2xl border bg-card shadow-xs">
                <div class="border-b px-5 py-3.5">
                    <h2 class="text-base font-bold">Performa per Menu</h2>
                </div>
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-muted/60 text-xs text-muted-foreground uppercase">
                        <tr>
                            <th class="px-4 py-2.5">Menu</th>
                            <th class="px-4 py-2.5">Qty</th>
                            <th class="px-4 py-2.5">Omzet</th>
                            <th class="px-4 py-2.5">Margin</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="row in menuRows" :key="row.menu_item_id">
                            <td class="px-4 py-2.5 font-semibold">{{ row.name }}</td>
                            <td class="font-money px-4 py-2.5">{{ row.qty_sold }}</td>
                            <td class="font-money px-4 py-2.5 font-semibold">
                                {{ formatRupiah(row.revenue) }}
                            </td>
                            <td class="font-money px-4 py-2.5 font-bold text-emerald-700">
                                {{ row.margin_percent }}%
                            </td>
                        </tr>
                        <tr v-if="menuRows.length === 0">
                            <td colspan="4" class="px-4 py-6 text-center text-muted-foreground">
                                Belum ada penjualan menu.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
