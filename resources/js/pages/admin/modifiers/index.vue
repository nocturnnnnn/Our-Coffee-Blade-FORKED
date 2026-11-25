<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Edit2, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import { formatRupiah } from '@/lib/format';

type ModifierOptionItem = {
    id: number;
    modifier_group_id: number;
    name: string;
    price_delta: number;
    is_default: boolean;
    is_available: boolean;
    ingredient_id: number | null;
    ingredient_name: string | null;
    ingredient_unit: string | null;
    ingredient_qty: number | null;
    sort_order: number;
};

type ModifierGroupItem = {
    id: number;
    name: string;
    selection_type: 'single' | 'multiple';
    is_required: boolean;
    min_select: number;
    max_select: number;
    sort_order: number;
    menu_items_count: number;
    options: ModifierOptionItem[];
};

type IngredientOption = {
    id: number;
    name: string;
    unit: string;
};

const props = defineProps<{
    modifierGroups: ModifierGroupItem[];
    ingredients: IngredientOption[];
}>();

const groupDialogOpen = ref(false);
const editingGroup = ref<ModifierGroupItem | null>(null);

const groupForm = useForm({
    name: '',
    selection_type: 'single' as 'single' | 'multiple',
    is_required: false,
    min_select: 0,
    max_select: 1,
    sort_order: 0,
});

const optionDialogOpen = ref(false);
const targetGroup = ref<ModifierGroupItem | null>(null);
const editingOption = ref<ModifierOptionItem | null>(null);

const optionForm = useForm({
    name: '',
    price_delta: 0,
    is_default: false,
    is_available: true,
    ingredient_id: '' as string | number,
    ingredient_qty: '',
    sort_order: 0,
});

function openCreateGroup() {
    editingGroup.value = null;
    groupForm.name = '';
    groupForm.selection_type = 'single';
    groupForm.is_required = false;
    groupForm.min_select = 0;
    groupForm.max_select = 1;
    groupForm.sort_order = props.modifierGroups.length + 1;
    groupDialogOpen.value = true;
}

function openEditGroup(group: ModifierGroupItem) {
    editingGroup.value = group;
    groupForm.name = group.name;
    groupForm.selection_type = group.selection_type;
    groupForm.is_required = group.is_required;
    groupForm.min_select = group.min_select;
    groupForm.max_select = group.max_select;
    groupForm.sort_order = group.sort_order;
    groupDialogOpen.value = true;
}

function submitGroup() {
    if (editingGroup.value) {
        groupForm.put(`/admin/modifiers/${editingGroup.value.id}`, {
            onSuccess: () => {
                groupDialogOpen.value = false;
            },
        });
    } else {
        groupForm.post('/admin/modifiers', {
            onSuccess: () => {
                groupDialogOpen.value = false;
            },
        });
    }
}

function deleteGroup(group: ModifierGroupItem) {
    if (window.confirm(`Hapus grup "${group.name}"?`)) {
        router.delete(`/admin/modifiers/${group.id}`);
    }
}

function openCreateOption(group: ModifierGroupItem) {
    targetGroup.value = group;
    editingOption.value = null;
    optionForm.name = '';
    optionForm.price_delta = 0;
    optionForm.is_default = false;
    optionForm.is_available = true;
    optionForm.ingredient_id = '';
    optionForm.ingredient_qty = '';
    optionForm.sort_order = group.options.length + 1;
    optionDialogOpen.value = true;
}

function openEditOption(group: ModifierGroupItem, option: ModifierOptionItem) {
    targetGroup.value = group;
    editingOption.value = option;
    optionForm.name = option.name;
    optionForm.price_delta = option.price_delta;
    optionForm.is_default = option.is_default;
    optionForm.is_available = option.is_available;
    optionForm.ingredient_id = option.ingredient_id ?? '';
    optionForm.ingredient_qty =
        option.ingredient_qty !== null ? String(option.ingredient_qty) : '';
    optionForm.sort_order = option.sort_order;
    optionDialogOpen.value = true;
}

function submitOption() {
    if (!targetGroup.value) return;

    const prepared = optionForm.transform((data) => ({
        ...data,
        ingredient_id: data.ingredient_id ? Number(data.ingredient_id) : null,
        ingredient_qty: data.ingredient_id ? Number(data.ingredient_qty) : null,
    }));

    if (editingOption.value) {
        prepared.put(
            `/admin/modifiers/${targetGroup.value.id}/options/${editingOption.value.id}`,
            {
                onSuccess: () => {
                    optionDialogOpen.value = false;
                },
            },
        );
    } else {
        prepared.post(`/admin/modifiers/${targetGroup.value.id}/options`, {
            onSuccess: () => {
                optionDialogOpen.value = false;
            },
        });
    }
}

function deleteOption(group: ModifierGroupItem, opt: ModifierOptionItem) {
    if (window.confirm(`Hapus opsi "${opt.name}"?`)) {
        router.delete(`/admin/modifiers/${group.id}/options/${opt.id}`);
    }
}
</script>

<template>
    <Head title="Grup & Opsi Modifier" />
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    Grup & Opsi Modifier
                </h1>
                <p class="text-sm text-muted-foreground">
                    Atur pilihan kustomisasi minuman (Suhu, Level Gula, Jenis Susu, Tambahan) beserta dampak harga dan BOM.
                </p>
            </div>
            <Button @click="openCreateGroup" class="min-h-11 gap-2">
                <Plus class="size-4" />
                Tambah Grup Modifier
            </Button>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <div
                v-for="group in modifierGroups"
                :key="group.id"
                class="flex flex-col rounded-2xl border bg-card p-5 shadow-xs"
            >
                <div class="flex items-start justify-between gap-3 border-b pb-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-bold">{{ group.name }}</h2>
                            <Badge :variant="group.is_required ? 'default' : 'secondary'">
                                {{ group.is_required ? 'Wajib' : 'Opsional' }}
                            </Badge>
                            <Badge variant="outline">
                                {{
                                    group.selection_type === 'single'
                                        ? 'Pilih 1'
                                        : `Maks ${group.max_select}`
                                }}
                            </Badge>
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Dipakai di {{ group.menu_items_count }} menu
                        </p>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <Button variant="outline" size="sm" @click="openEditGroup(group)">
                            <Edit2 class="size-4" />
                        </Button>
                        <Button variant="outline" size="sm" @click="deleteGroup(group)">
                            <Trash2 class="size-4 text-destructive" />
                        </Button>
                    </div>
                </div>

                <div class="mt-4 flex-1 space-y-2">
                    <div
                        v-for="opt in group.options"
                        :key="opt.id"
                        class="flex items-center justify-between gap-3 rounded-xl border bg-muted/30 px-3 py-2.5 text-sm"
                    >
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-semibold">{{ opt.name }}</span>
                                <Badge v-if="opt.is_default" variant="secondary">
                                    Default
                                </Badge>
                                <Badge v-if="!opt.is_available" variant="destructive">
                                    Nonaktif
                                </Badge>
                            </div>
                            <p v-if="opt.ingredient_name" class="text-xs text-muted-foreground">
                                BOM: {{ opt.ingredient_name }} ({{ opt.ingredient_qty }}
                                {{ opt.ingredient_unit }})
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="font-money text-xs font-semibold text-primary">
                                {{
                                    opt.price_delta > 0
                                        ? `+${formatRupiah(opt.price_delta)}`
                                        : 'Gratis'
                                }}
                            </span>
                            <Button variant="ghost" size="sm" @click="openEditOption(group, opt)">
                                <Edit2 class="size-3.5" />
                            </Button>
                            <Button variant="ghost" size="sm" @click="deleteOption(group, opt)">
                                <Trash2 class="size-3.5 text-destructive" />
                            </Button>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-2">
                    <Button
                        variant="outline"
                        size="sm"
                        class="w-full gap-1.5"
                        @click="openCreateOption(group)"
                    >
                        <Plus class="size-4" />
                        Tambah Opsi ke {{ group.name }}
                    </Button>
                </div>
            </div>
        </div>
    </div>

    <Dialog :open="groupDialogOpen" @close="groupDialogOpen = false">
        <div class="mb-4">
            <h2 class="text-lg font-bold">
                {{ editingGroup ? 'Edit Grup Modifier' : 'Tambah Grup Modifier' }}
            </h2>
        </div>
        <form @submit.prevent="submitGroup" class="space-y-4">
            <div class="space-y-1.5">
                <Label for="grp-name">Nama Grup</Label>
                <Input id="grp-name" v-model="groupForm.name" required />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <Label for="grp-type">Tipe Pilihan</Label>
                    <select
                        id="grp-type"
                        v-model="groupForm.selection_type"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option value="single">Pilih Satu</option>
                        <option value="multiple">Pilih Banyak</option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <Label for="grp-max">Maksimal Pilih</Label>
                    <Input
                        id="grp-max"
                        type="number"
                        min="1"
                        :model-value="groupForm.max_select"
                        @update:model-value="groupForm.max_select = Number($event)"
                    />
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input
                    type="checkbox"
                    :checked="groupForm.is_required"
                    @change="
                        groupForm.is_required = ($event.target as HTMLInputElement).checked;
                        groupForm.min_select = groupForm.is_required ? 1 : 0;
                    "
                />
                Wajib dipilih pelanggan
            </label>
            <div class="mt-4 flex justify-end gap-2">
                <Button variant="outline" @click="groupDialogOpen = false">Batal</Button>
                <Button type="submit" :disabled="groupForm.processing">Simpan</Button>
            </div>
        </form>
    </Dialog>

    <Dialog :open="optionDialogOpen" @close="optionDialogOpen = false">
        <div class="mb-4">
            <h2 class="text-lg font-bold">
                {{
                    editingOption
                        ? `Edit Opsi (${targetGroup?.name})`
                        : `Tambah Opsi (${targetGroup?.name})`
                }}
            </h2>
        </div>
        <form @submit.prevent="submitOption" class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <Label for="opt-name">Nama Opsi</Label>
                    <Input id="opt-name" v-model="optionForm.name" required />
                </div>
                <div class="space-y-1.5">
                    <Label for="opt-delta">Tambahan Harga (Rp)</Label>
                    <Input
                        id="opt-delta"
                        type="number"
                        min="0"
                        :model-value="optionForm.price_delta"
                        @update:model-value="optionForm.price_delta = Number($event)"
                        required
                    />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <Label for="opt-ing">Potong Bahan Baku (Opsional)</Label>
                    <select
                        id="opt-ing"
                        v-model="optionForm.ingredient_id"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option value="">Tidak memotong bahan</option>
                        <option v-for="ing in ingredients" :key="ing.id" :value="ing.id">
                            {{ ing.name }} ({{ ing.unit }})
                        </option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <Label for="opt-qty">Takaran Bahan</Label>
                    <Input
                        id="opt-qty"
                        type="number"
                        step="0.001"
                        min="0.001"
                        :disabled="!optionForm.ingredient_id"
                        v-model="optionForm.ingredient_qty"
                        :required="Boolean(optionForm.ingredient_id)"
                    />
                </div>
            </div>

            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" v-model="optionForm.is_default" />
                    Pilihan Default
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" v-model="optionForm.is_available" />
                    Tersedia
                </label>
            </div>

            <div class="mt-4 flex justify-end gap-2">
                <Button variant="outline" @click="optionDialogOpen = false">Batal</Button>
                <Button type="submit" :disabled="optionForm.processing">Simpan</Button>
            </div>
        </form>
    </Dialog>
</template>
