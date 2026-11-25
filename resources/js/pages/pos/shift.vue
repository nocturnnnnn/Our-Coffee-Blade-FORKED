<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowRight, Clock, Lock, Unlock } from 'lucide-vue-next';
import { computed } from 'vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import { formatDateWib, formatRupiah, formatTimeWib } from '@/lib/format';
import { cn } from '@/lib/utils';

type ActiveShiftDetail = {
    id: number;
    opening_cash: number;
    cash_sales_total: number;
    non_cash_sales_total: number;
    expected_cash: number;
    orders_count: number;
    opened_at: string;
} | null;

type ClosedShiftRow = {
    id: number;
    cashier_name: string;
    opening_cash: number;
    expected_cash: number;
    actual_cash: number;
    variance: number;
    variance_reason: string | null;
    opened_at: string;
    closed_at: string | null;
};

const props = defineProps<{
    activeShiftDetail: ActiveShiftDetail;
    recentShifts: ClosedShiftRow[];
}>();

const OPENING_PRESETS = [300000, 500000, 1000000];

const openForm = useForm({
    opening_cash: '500000',
});

const closeForm = useForm({
    actual_cash: props.activeShiftDetail
        ? String(props.activeShiftDetail.expected_cash)
        : '0',
    variance_reason: '',
});

function handleOpen() {
    openForm
        .transform((data) => ({
            opening_cash: Number(data.opening_cash) || 0,
        }))
        .post('/pos/shift/open');
}

function handleClose() {
    closeForm
        .transform((data) => ({
            actual_cash: Number(data.actual_cash) || 0,
            variance_reason: data.variance_reason.trim() || null,
        }))
        .post('/pos/shift/close');
}

const actualCashNum = computed(() => Number(closeForm.actual_cash) || 0);
const variance = computed(() =>
    props.activeShiftDetail
        ? actualCashNum.value - props.activeShiftDetail.expected_cash
        : 0,
);
</script>

<template>
    <Head title="Shift Kasir" />
    <div class="mx-auto max-w-4xl space-y-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Manajemen Shift Kasir</h1>
            <p class="text-sm text-muted-foreground">
                Buka shift dengan modal awal laci kas, atau lakukan rekonsiliasi kas akhir shift.
            </p>
        </div>

        <div v-if="!activeShiftDetail" class="rounded-2xl border bg-card p-6 shadow-xs">
            <div class="flex items-center gap-3 border-b pb-4">
                <div class="flex size-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    <Unlock class="size-6" />
                </div>
                <div>
                    <h2 class="text-lg font-bold">Buka Shift Baru</h2>
                    <p class="text-xs text-muted-foreground">
                        Masukkan jumlah uang tunai awal di laci kasir untuk mengaktifkan layar POS.
                    </p>
                </div>
            </div>

            <form @submit.prevent="handleOpen" class="mt-5 space-y-4">
                <div class="space-y-2">
                    <Label for="opening-cash">Modal Awal Laci Kas (Rp)</Label>
                    <Input
                        id="opening-cash"
                        v-model="openForm.opening_cash"
                        type="number"
                        min="0"
                        class="font-money h-14 text-lg font-bold"
                        required
                    />
                    <p v-if="openForm.errors.opening_cash" class="text-xs text-destructive">
                        {{ openForm.errors.opening_cash }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Button
                        v-for="preset in OPENING_PRESETS"
                        :key="preset"
                        variant="outline"
                        @click="openForm.opening_cash = String(preset)"
                        class="font-money min-h-12"
                    >
                        {{ formatRupiah(preset) }}
                    </Button>
                </div>

                <Button
                    type="submit"
                    :disabled="openForm.processing"
                    class="min-h-14 w-full gap-2 text-base font-bold"
                >
                    <Clock class="size-5" />
                    Buka Shift & Masuk POS
                </Button>
            </form>
        </div>

        <div v-else class="rounded-2xl border bg-card p-6 shadow-xs">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b pb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-bold">
                            Shift Aktif #{{ activeShiftDetail.id }}
                        </h2>
                        <Badge class="bg-emerald-600 text-white">Terbuka</Badge>
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Dibuka {{ formatDateWib(activeShiftDetail.opened_at) }}
                        {{ formatTimeWib(activeShiftDetail.opened_at) }} WIB ·
                        {{ activeShiftDetail.orders_count }} transaksi lunas
                    </p>
                </div>

                <Link href="/pos">
                    <Button class="min-h-12 gap-2 font-bold">
                        Lanjut ke POS
                        <ArrowRight class="size-4" />
                    </Button>
                </Link>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-4">
                <div class="rounded-xl bg-muted/60 p-3.5">
                    <p class="text-xs text-muted-foreground">Modal Awal</p>
                    <p class="font-money mt-1 text-base font-bold">
                        {{ formatRupiah(activeShiftDetail.opening_cash) }}
                    </p>
                </div>
                <div class="rounded-xl bg-muted/60 p-3.5">
                    <p class="text-xs text-muted-foreground">Penjualan Tunai</p>
                    <p class="font-money mt-1 text-base font-bold text-emerald-700">
                        {{ formatRupiah(activeShiftDetail.cash_sales_total) }}
                    </p>
                </div>
                <div class="rounded-xl bg-muted/60 p-3.5">
                    <p class="text-xs text-muted-foreground">Non-Tunai (EDC/QRIS)</p>
                    <p class="font-money mt-1 text-base font-bold">
                        {{ formatRupiah(activeShiftDetail.non_cash_sales_total) }}
                    </p>
                </div>
                <div class="rounded-xl border border-primary/30 bg-primary/5 p-3.5">
                    <p class="text-xs text-muted-foreground">Kas Seharusnya</p>
                    <p class="font-money mt-1 text-base font-extrabold text-primary">
                        {{ formatRupiah(activeShiftDetail.expected_cash) }}
                    </p>
                </div>
            </div>

            <form @submit.prevent="handleClose" class="mt-6 space-y-4 border-t pt-5">
                <h3 class="text-sm font-bold tracking-wide text-muted-foreground uppercase">
                    Tutup Shift & Rekonsiliasi Laci Kas
                </h3>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <Label for="actual-cash">Uang Fisik Aktual di Laci (Rp)</Label>
                        <Input
                            id="actual-cash"
                            v-model="closeForm.actual_cash"
                            type="number"
                            min="0"
                            class="font-money h-12 text-base font-bold"
                            required
                        />
                    </div>
                    <div class="space-y-1.5">
                        <Label>Selisih Kas (Variance)</Label>
                        <div
                            :class="
                                cn(
                                    'font-money flex h-12 items-center rounded-md border px-3 text-base font-bold',
                                    variance === 0
                                        ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                                        : 'border-amber-300 bg-amber-50 text-amber-900',
                                )
                            "
                        >
                            {{
                                variance === 0
                                    ? 'Sesuai (Rp 0)'
                                    : `${variance > 0 ? '+' : '-'}${formatRupiah(Math.abs(variance))}`
                            }}
                        </div>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <Label for="variance-reason">
                        Alasan Selisih {{ variance !== 0 ? '(Wajib)' : '(Opsional)' }}
                    </Label>
                    <Input
                        id="variance-reason"
                        v-model="closeForm.variance_reason"
                        :required="variance !== 0"
                        placeholder="Jelaskan penyebab selisih jika ada"
                        class="h-12"
                    />
                    <p v-if="closeForm.errors.variance_reason" class="text-xs text-destructive">
                        {{ closeForm.errors.variance_reason }}
                    </p>
                </div>

                <Button
                    type="submit"
                    variant="destructive"
                    :disabled="closeForm.processing"
                    class="min-h-12 gap-2 font-bold"
                >
                    <Lock class="size-4" />
                    Tutup Shift Sekarang
                </Button>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl border bg-card shadow-xs">
            <div class="border-b px-5 py-3.5">
                <h2 class="text-base font-bold">Riwayat Tutup Shift Terbaru</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-muted/60 text-xs text-muted-foreground uppercase">
                        <tr>
                            <th class="px-4 py-3">Kasir</th>
                            <th class="px-4 py-3">Tutup</th>
                            <th class="px-4 py-3">Modal Awal</th>
                            <th class="px-4 py-3">Seharusnya</th>
                            <th class="px-4 py-3">Aktual</th>
                            <th class="px-4 py-3">Selisih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="row in recentShifts" :key="row.id">
                            <td class="px-4 py-3 font-semibold">{{ row.cashier_name }}</td>
                            <td class="px-4 py-3 text-xs text-muted-foreground">
                                {{
                                    row.closed_at
                                        ? `${formatDateWib(row.closed_at)} ${formatTimeWib(row.closed_at)}`
                                        : '-'
                                }}
                            </td>
                            <td class="font-money px-4 py-3">{{ formatRupiah(row.opening_cash) }}</td>
                            <td class="font-money px-4 py-3">{{ formatRupiah(row.expected_cash) }}</td>
                            <td class="font-money px-4 py-3">{{ formatRupiah(row.actual_cash) }}</td>
                            <td class="px-4 py-3">
                                <span
                                    :class="
                                        cn(
                                            'font-money font-semibold',
                                            row.variance === 0
                                                ? 'text-emerald-700'
                                                : 'text-amber-800',
                                        )
                                    "
                                >
                                    {{ row.variance >= 0 ? '' : '-'
                                    }}{{ formatRupiah(Math.abs(row.variance)) }}
                                </span>
                                <p v-if="row.variance_reason" class="text-xs text-muted-foreground">
                                    {{ row.variance_reason }}
                                </p>
                            </td>
                        </tr>
                        <tr v-if="recentShifts.length === 0">
                            <td colspan="6" class="px-4 py-6 text-center text-muted-foreground">
                                Belum ada riwayat shift yang ditutup.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
