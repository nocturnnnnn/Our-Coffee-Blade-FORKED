<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowDownToLine,
    ClipboardCheck,
    Edit2,
    Plus,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import StockLevelBar from '@/components/StockLevelBar.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import { formatDateWib, formatRupiah, formatTimeWib } from '@/lib/format';
import { cn } from '@/lib/utils';

type IngredientItem = {
    id: number;
    name: string;
    sku: string | null;
    unit: 'g' | 'ml' | 'pcs';
    current_stock: number;
    min_stock: number;
    cost_per_unit: number;
    default_distributor_id: number | null;
    default_distributor_name: string | null;
    is_active: boolean;
    is_low_stock: boolean;
};

type DistributorOption = {
    id: number;
    name: string;
};

type MovementItem = {
    id: number;
    ingredient_id: number;
    ingredient_name: string;
    unit: string;
    type: string;
    type_label: string;
    quantity_change: number;
    stock_before: number;
    stock_after: number;
    unit_cost: number | null;
    total_cost: number | null;
    distributor_name: string | null;
    notes: string | null;
    creator_name: string | null;
    created_at: string | null;
};

const props = defineProps<{
    ingredients: IngredientItem[];
    distributors: DistributorOption[];
    movements: MovementItem[];
}>();

const tab = ref<'stock' | 'movements'>('stock');

const ingredientModalOpen = ref(false);
const editingIngredient = ref<IngredientItem | null>(null);
const ingForm = useForm({
    name: '',
    sku: '',
    unit: 'g' as 'g' | 'ml' | 'pcs',
    current_stock: 0,
    min_stock: 100,
    cost_per_unit: 0,
    default_distributor_id: '' as string | number,
    is_active: true,
});

const restockTarget = ref<IngredientItem | null>(null);
const restockForm = useForm({
    quantity: '',
    total_cost: '',
    distributor_id: '' as string | number,
    notes: '',
});

const wasteTarget = ref<IngredientItem | null>(null);
const wasteForm = useForm({
    quantity: '',
    reason: '',
});

const opnameTarget = ref<IngredientItem | null>(null);
const opnameForm = useForm({
    actual_stock: '',
    reason: '',
});

function openCreateIngredient() {
    editingIngredient.value = null;
    ingForm.name = '';
    ingForm.sku = '';
    ingForm.unit = 'g';
    ingForm.current_stock = 0;
    ingForm.min_stock = 100;
    ingForm.cost_per_unit = 0;
    ingForm.default_distributor_id = props.distributors[0]?.id ?? '';
    ingForm.is_active = true;
    ingredientModalOpen.value = true;
}

function openEditIngredient(ing: IngredientItem) {
    editingIngredient.value = ing;
    ingForm.name = ing.name;
    ingForm.sku = ing.sku ?? '';
    ingForm.unit = ing.unit;
    ingForm.current_stock = ing.current_stock;
    ingForm.min_stock = ing.min_stock;
    ingForm.cost_per_unit = ing.cost_per_unit;
    ingForm.default_distributor_id = ing.default_distributor_id ?? '';
    ingForm.is_active = ing.is_active;
    ingredientModalOpen.value = true;
}

function submitIngredient() {
    const prepared = ingForm.transform((data) => ({
        ...data,
        default_distributor_id: data.default_distributor_id
            ? Number(data.default_distributor_id)
            : null,
    }));

    if (editingIngredient.value) {
        prepared.put(
            `/admin/inventory/ingredients/${editingIngredient.value.id}`,
            {
                onSuccess: () => {
                    ingredientModalOpen.value = false;
                },
            },
        );
    } else {
        prepared.post('/admin/inventory/ingredients', {
            onSuccess: () => {
                ingredientModalOpen.value = false;
            },
        });
    }
}

function openRestock(ing: IngredientItem) {
    restockTarget.value = ing;
    restockForm.quantity = '';
    restockForm.total_cost = '';
    restockForm.distributor_id = ing.default_distributor_id ?? '';
    restockForm.notes = '';
}

function submitRestock() {
    if (!restockTarget.value) return;
    restockForm
        .transform((data) => ({
            ...data,
            quantity: Number(data.quantity),
            total_cost: Number(data.total_cost),
            distributor_id: data.distributor_id
                ? Number(data.distributor_id)
                : null,
        }))
        .post(`/admin/inventory/ingredients/${restockTarget.value.id}/restock`, {
            onSuccess: () => {
                restockTarget.value = null;
            },
        });
}

function submitWaste() {
    if (!wasteTarget.value) return;
    wasteForm
        .transform((data) => ({
            ...data,
            quantity: Number(data.quantity),
        }))
        .post(`/admin/inventory/ingredients/${wasteTarget.value.id}/waste`, {
            onSuccess: () => {
                wasteTarget.value = null;
            },
        });
}

function submitOpname() {
    if (!opnameTarget.value) return;
    opnameForm
        .transform((data) => ({
            ...data,
            actual_stock: Number(data.actual_stock),
        }))
        .post(`/admin/inventory/ingredients/${opnameTarget.value.id}/opname`, {
            onSuccess: () => {
                opnameTarget.value = null;
            },
        });
}

const estimatedUnitCost = computed(() => {
    const qty = Number(restockForm.quantity) || 0;
    const cost = Number(restockForm.total_cost) || 0;
    return qty > 0 ? Math.round(cost / qty) : 0;
});
</script>

<template>
    <Head title="Inventaris & Stock In" />
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    Inventaris Bahan Baku
                </h1>
                <p class="text-sm text-muted-foreground">
                    Pantau stok real-time, catat Stock In dari distributor, waste, dan stock opname.
                </p>
            </div>
            <Button @click="openCreateIngredient" class="min-h-11 gap-2">
                <Plus class="size-4" />
                Tambah Bahan Baku
            </Button>
        </div>

        <div class="flex items-center gap-2 border-b pb-2">
            <button
                type="button"
                @click="tab = 'stock'"
                :class="
                    cn(
                        'rounded-xl px-4 py-2 text-sm font-semibold transition-colors',
                        tab === 'stock'
                            ? 'bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:bg-muted',
                    )
                "
            >
                Stok Bahan Baku ({{ ingredients.length }})
            </button>
            <button
                type="button"
                @click="tab = 'movements'"
                :class="
                    cn(
                        'rounded-xl px-4 py-2 text-sm font-semibold transition-colors',
                        tab === 'movements'
                            ? 'bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:bg-muted',
                    )
                "
            >
                Riwayat Mutasi ({{ movements.length }})
            </button>
        </div>

        <div
            v-if="tab === 'stock'"
            class="overflow-x-auto rounded-2xl border bg-card shadow-xs"
        >
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-muted/60 text-xs text-muted-foreground uppercase">
                    <tr>
                        <th class="px-4 py-3">Bahan Baku</th>
                        <th class="px-4 py-3">Level Stok</th>
                        <th class="px-4 py-3">Harga / Unit</th>
                        <th class="px-4 py-3">Distributor</th>
                        <th class="px-4 py-3 text-right">Aksi Stok</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="ing in ingredients" :key="ing.id" class="hover:bg-muted/30">
                        <td class="px-4 py-3">
                            <p class="font-semibold">{{ ing.name }}</p>
                            <p class="font-money text-xs text-muted-foreground">
                                {{ ing.sku ?? '-' }} · Unit: {{ ing.unit }}
                            </p>
                        </td>
                        <td class="px-4 py-3">
                            <StockLevelBar
                                :current-stock="ing.current_stock"
                                :min-stock="ing.min_stock"
                                :unit="ing.unit"
                            />
                        </td>
                        <td class="font-money px-4 py-3 font-semibold">
                            {{ formatRupiah(ing.cost_per_unit) }} / {{ ing.unit }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ ing.default_distributor_name ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex flex-wrap justify-end gap-1.5">
                                <Button size="sm" @click="openRestock(ing)" class="gap-1">
                                    <ArrowDownToLine class="size-3.5" />
                                    Stock In
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="
                                        wasteTarget = ing;
                                        wasteForm.quantity = '';
                                        wasteForm.reason = '';
                                    "
                                    class="gap-1"
                                >
                                    <AlertTriangle class="size-3.5 text-amber-700" />
                                    Waste
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="
                                        opnameTarget = ing;
                                        opnameForm.actual_stock = String(ing.current_stock);
                                        opnameForm.reason = '';
                                    "
                                    class="gap-1"
                                >
                                    <ClipboardCheck class="size-3.5" />
                                    Opname
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="openEditIngredient(ing)"
                                >
                                    <Edit2 class="size-3.5" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-else class="overflow-x-auto rounded-2xl border bg-card shadow-xs">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-muted/60 text-xs text-muted-foreground uppercase">
                    <tr>
                        <th class="px-4 py-3">Waktu</th>
                        <th class="px-4 py-3">Bahan Baku</th>
                        <th class="px-4 py-3">Tipe</th>
                        <th class="px-4 py-3">Perubahan</th>
                        <th class="px-4 py-3">Sebelum → Sesudah</th>
                        <th class="px-4 py-3">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="mov in movements" :key="mov.id" class="hover:bg-muted/30">
                        <td class="px-4 py-3 text-xs text-muted-foreground">
                            {{
                                mov.created_at
                                    ? `${formatDateWib(mov.created_at)} ${formatTimeWib(mov.created_at)}`
                                    : '-'
                            }}
                        </td>
                        <td class="px-4 py-3 font-semibold">{{ mov.ingredient_name }}</td>
                        <td class="px-4 py-3">
                            <Badge variant="outline">{{ mov.type_label }}</Badge>
                        </td>
                        <td
                            :class="
                                cn(
                                    'font-money px-4 py-3 font-semibold',
                                    mov.quantity_change >= 0
                                        ? 'text-emerald-700'
                                        : 'text-red-700',
                                )
                            "
                        >
                            {{ mov.quantity_change >= 0 ? '+' : '' }}{{ mov.quantity_change }}
                            {{ mov.unit }}
                        </td>
                        <td class="font-money px-4 py-3 text-xs">
                            {{ mov.stock_before }} → {{ mov.stock_after }} {{ mov.unit }}
                        </td>
                        <td class="px-4 py-3 text-xs text-muted-foreground">
                            {{ mov.notes ?? mov.distributor_name ?? '-' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <Dialog :open="ingredientModalOpen" @close="ingredientModalOpen = false">
        <div class="mb-4">
            <h2 class="text-lg font-bold">
                {{ editingIngredient ? 'Edit Bahan Baku' : 'Tambah Bahan Baku' }}
            </h2>
        </div>
        <form @submit.prevent="submitIngredient" class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <Label for="ing-name">Nama Bahan</Label>
                    <Input id="ing-name" v-model="ingForm.name" required />
                </div>
                <div class="space-y-1.5">
                    <Label for="ing-sku">SKU</Label>
                    <Input id="ing-sku" v-model="ingForm.sku" />
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div class="space-y-1.5">
                    <Label for="ing-unit">Satuan</Label>
                    <select
                        id="ing-unit"
                        v-model="ingForm.unit"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option value="g">Gram (g)</option>
                        <option value="ml">Mililiter (ml)</option>
                        <option value="pcs">Pcs</option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <Label for="ing-min">Batas Min</Label>
                    <Input
                        id="ing-min"
                        type="number"
                        step="0.001"
                        min="0"
                        :model-value="ingForm.min_stock"
                        @update:model-value="ingForm.min_stock = Number($event)"
                        required
                    />
                </div>
                <div class="space-y-1.5">
                    <Label for="ing-cost">Harga/Unit (Rp)</Label>
                    <Input
                        id="ing-cost"
                        type="number"
                        min="0"
                        :model-value="ingForm.cost_per_unit"
                        @update:model-value="ingForm.cost_per_unit = Number($event)"
                        required
                    />
                </div>
            </div>

            <div v-if="!editingIngredient" class="space-y-1.5">
                <Label for="ing-init">Stok Awal</Label>
                <Input
                    id="ing-init"
                    type="number"
                    step="0.001"
                    min="0"
                    :model-value="ingForm.current_stock"
                    @update:model-value="ingForm.current_stock = Number($event)"
                    required
                />
            </div>

            <div class="space-y-1.5">
                <Label for="ing-dist">Distributor Default</Label>
                <select
                    id="ing-dist"
                    v-model="ingForm.default_distributor_id"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                >
                    <option value="">Pilih Distributor</option>
                    <option v-for="d in distributors" :key="d.id" :value="d.id">
                        {{ d.name }}
                    </option>
                </select>
            </div>

            <div class="mt-4 flex justify-end gap-2">
                <Button variant="outline" @click="ingredientModalOpen = false">
                    Batal
                </Button>
                <Button type="submit" :disabled="ingForm.processing">Simpan</Button>
            </div>
        </form>
    </Dialog>

    <Dialog :open="restockTarget !== null" @close="restockTarget = null">
        <div class="mb-4">
            <h2 class="text-lg font-bold">Stock In — {{ restockTarget?.name }}</h2>
        </div>
        <form @submit.prevent="submitRestock" class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <Label for="rs-qty">Jumlah Masuk ({{ restockTarget?.unit }})</Label>
                    <Input
                        id="rs-qty"
                        type="number"
                        step="0.001"
                        min="0.001"
                        v-model="restockForm.quantity"
                        required
                    />
                </div>
                <div class="space-y-1.5">
                    <Label for="rs-cost">Total Harga Beli (Rp)</Label>
                    <Input
                        id="rs-cost"
                        type="number"
                        min="0"
                        v-model="restockForm.total_cost"
                        required
                    />
                </div>
            </div>

            <p
                v-if="estimatedUnitCost > 0"
                class="font-money rounded-lg bg-muted px-3 py-2 text-xs font-medium"
            >
                Harga beli terbaru: {{ formatRupiah(estimatedUnitCost) }} /
                {{ restockTarget?.unit }}
            </p>

            <div class="space-y-1.5">
                <Label for="rs-dist">Distributor</Label>
                <select
                    id="rs-dist"
                    v-model="restockForm.distributor_id"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                >
                    <option value="">Pilih Distributor</option>
                    <option v-for="d in distributors" :key="d.id" :value="d.id">
                        {{ d.name }}
                    </option>
                </select>
            </div>

            <div class="space-y-1.5">
                <Label for="rs-notes">Catatan</Label>
                <Input
                    id="rs-notes"
                    v-model="restockForm.notes"
                    placeholder="No. faktur / keterangan"
                />
            </div>

            <div class="mt-4 flex justify-end gap-2">
                <Button variant="outline" @click="restockTarget = null">Batal</Button>
                <Button type="submit" :disabled="restockForm.processing">
                    Simpan Stock In
                </Button>
            </div>
        </form>
    </Dialog>

    <Dialog :open="wasteTarget !== null" @close="wasteTarget = null">
        <div class="mb-4">
            <h2 class="text-lg font-bold">Catat Waste — {{ wasteTarget?.name }}</h2>
        </div>
        <form @submit.prevent="submitWaste" class="space-y-4">
            <div class="space-y-1.5">
                <Label for="ws-qty">Jumlah Terbuang ({{ wasteTarget?.unit }})</Label>
                <Input
                    id="ws-qty"
                    type="number"
                    step="0.001"
                    min="0.001"
                    v-model="wasteForm.quantity"
                    required
                />
            </div>
            <div class="space-y-1.5">
                <Label for="ws-reason">Alasan Waste</Label>
                <Input
                    id="ws-reason"
                    v-model="wasteForm.reason"
                    required
                    placeholder="Contoh: Kedaluwarsa / tumpah"
                />
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <Button variant="outline" @click="wasteTarget = null">Batal</Button>
                <Button type="submit" :disabled="wasteForm.processing">
                    Simpan Waste
                </Button>
            </div>
        </form>
    </Dialog>

    <Dialog :open="opnameTarget !== null" @close="opnameTarget = null">
        <div class="mb-4">
            <h2 class="text-lg font-bold">Stock Opname — {{ opnameTarget?.name }}</h2>
        </div>
        <form @submit.prevent="submitOpname" class="space-y-4">
            <p class="font-money text-xs text-muted-foreground">
                Stok tercatat saat ini: {{ opnameTarget?.current_stock }}
                {{ opnameTarget?.unit }}
            </p>
            <div class="space-y-1.5">
                <Label for="op-actual">Stok Fisik Aktual ({{ opnameTarget?.unit }})</Label>
                <Input
                    id="op-actual"
                    type="number"
                    step="0.001"
                    min="0"
                    v-model="opnameForm.actual_stock"
                    required
                />
            </div>
            <div class="space-y-1.5">
                <Label for="op-reason">Alasan Penyesuaian</Label>
                <Input
                    id="op-reason"
                    v-model="opnameForm.reason"
                    required
                    placeholder="Contoh: Opname fisik akhir shift"
                />
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <Button variant="outline" @click="opnameTarget = null">Batal</Button>
                <Button type="submit" :disabled="opnameForm.processing">
                    Simpan Opname
                </Button>
            </div>
        </form>
    </Dialog>
</template>
