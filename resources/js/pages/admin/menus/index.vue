<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Coffee,
    Edit2,
    FlaskConical,
    Plus,
    Power,
    Search,
    Trash2,
} from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import { formatRupiah } from '@/lib/format';
import { cn } from '@/lib/utils';

type RecipeRow = {
    ingredient_id: number;
    ingredient_name: string;
    unit: string;
    cost_per_unit: number;
    quantity: number;
};

type MenuItemData = {
    id: number;
    category_id: number;
    category_name: string;
    name: string;
    slug: string;
    description: string | null;
    price: number;
    image_url: string | null;
    badge: 'best_seller' | 'new' | null;
    is_available: boolean;
    is_active: boolean;
    sort_order: number;
    cogs: number;
    gross_profit: number;
    gross_margin_percent: number;
    effective_available: boolean;
    unavailable_reason: string | null;
    max_servings: number | null;
    modifier_group_ids: number[];
    recipe_items: RecipeRow[];
};

type CategoryOption = {
    id: number;
    name: string;
    is_active: boolean;
};

type ModifierGroupOption = {
    id: number;
    name: string;
    selection_type: string;
    is_required: boolean;
};

type IngredientOption = {
    id: number;
    name: string;
    unit: string;
    cost_per_unit: number;
};

const props = defineProps<{
    menus: MenuItemData[];
    categories: CategoryOption[];
    modifierGroups: ModifierGroupOption[];
    ingredients: IngredientOption[];
    filters: {
        search: string;
        category_id: number | null;
    };
}>();

const search = ref(props.filters.search);
const selectedCategory = ref(
    props.filters.category_id ? String(props.filters.category_id) : '',
);

const menuDialogOpen = ref(false);
const editingMenu = ref<MenuItemData | null>(null);
const submittingMenu = ref(false);

const formState = reactive({
    category_id: props.categories[0]?.id ?? 0,
    name: '',
    description: '',
    price: 25000,
    badge: '',
    is_available: true,
    is_active: true,
    sort_order: 0,
    modifier_group_ids: [] as number[],
    image: null as File | null,
    remove_image: false,
});

const recipeMenu = ref<MenuItemData | null>(null);
const recipeRows = ref<Array<{ ingredient_id: number; quantity: string }>>([]);
const savingRecipe = ref(false);

function applyFilters(nextSearch: string, nextCat: string) {
    router.get(
        '/admin/menus',
        {
            search: nextSearch || undefined,
            category_id: nextCat || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function openCreateMenu() {
    editingMenu.value = null;
    formState.category_id = props.categories[0]?.id ?? 0;
    formState.name = '';
    formState.description = '';
    formState.price = 28000;
    formState.badge = '';
    formState.is_available = true;
    formState.is_active = true;
    formState.sort_order = props.menus.length + 1;
    formState.modifier_group_ids = [];
    formState.image = null;
    formState.remove_image = false;
    menuDialogOpen.value = true;
}

function openEditMenu(item: MenuItemData) {
    editingMenu.value = item;
    formState.category_id = item.category_id;
    formState.name = item.name;
    formState.description = item.description ?? '';
    formState.price = item.price;
    formState.badge = item.badge ?? '';
    formState.is_available = item.is_available;
    formState.is_active = item.is_active;
    formState.sort_order = item.sort_order;
    formState.modifier_group_ids = [...item.modifier_group_ids];
    formState.image = null;
    formState.remove_image = false;
    menuDialogOpen.value = true;
}

function toggleModifierGroup(groupId: number) {
    if (formState.modifier_group_ids.includes(groupId)) {
        formState.modifier_group_ids = formState.modifier_group_ids.filter(
            (id) => id !== groupId,
        );
    } else {
        formState.modifier_group_ids = [...formState.modifier_group_ids, groupId];
    }
}

function submitMenu() {
    submittingMenu.value = true;

    const payload: Record<string, unknown> = {
        category_id: formState.category_id,
        name: formState.name,
        description: formState.description || null,
        price: formState.price,
        badge: formState.badge || null,
        is_available: formState.is_available,
        is_active: formState.is_active,
        sort_order: formState.sort_order,
        modifier_group_ids: formState.modifier_group_ids,
    };

    if (formState.image) {
        payload.image = formState.image;
    }
    if (editingMenu.value) {
        payload._method = 'put';
        payload.remove_image = formState.remove_image;
    }

    const targetUrl = editingMenu.value
        ? `/admin/menus/${editingMenu.value.id}`
        : '/admin/menus';

    router.post(targetUrl, payload as never, {
        forceFormData: true,
        onSuccess: () => {
            menuDialogOpen.value = false;
        },
        onFinish: () => {
            submittingMenu.value = false;
        },
    });
}

function toggleAvailability(id: number) {
    router.patch(`/admin/menus/${id}/toggle-availability`);
}

function deleteMenu(item: MenuItemData) {
    if (window.confirm(`Hapus menu "${item.name}"?`)) {
        router.delete(`/admin/menus/${item.id}`);
    }
}

function openRecipeEditor(item: MenuItemData) {
    recipeMenu.value = item;
    recipeRows.value =
        item.recipe_items.length > 0
            ? item.recipe_items.map((row) => ({
                  ingredient_id: row.ingredient_id,
                  quantity: String(row.quantity),
              }))
            : props.ingredients[0]
              ? [{ ingredient_id: props.ingredients[0].id, quantity: '1' }]
              : [];
}

function addRecipeRow() {
    const usedIds = new Set(recipeRows.value.map((r) => r.ingredient_id));
    const nextIng =
        props.ingredients.find((ing) => !usedIds.has(ing.id)) ??
        props.ingredients[0];
    if (!nextIng) return;
    recipeRows.value.push({ ingredient_id: nextIng.id, quantity: '1' });
}

function saveRecipe() {
    if (!recipeMenu.value) return;
    savingRecipe.value = true;

    router.put(
        `/admin/menus/${recipeMenu.value.id}/recipe`,
        {
            ingredients: recipeRows.value.map((row) => ({
                ingredient_id: row.ingredient_id,
                quantity: Number(row.quantity),
            })),
        },
        {
            onSuccess: () => {
                recipeMenu.value = null;
            },
            onFinish: () => {
                savingRecipe.value = false;
            },
        },
    );
}

const previewCogsRounded = computed(() => {
    const sum = recipeRows.value.reduce((acc, row) => {
        const ing = props.ingredients.find((i) => i.id === row.ingredient_id);
        const qty = Number(row.quantity) || 0;
        return acc + (ing ? qty * ing.cost_per_unit : 0);
    }, 0);
    return Math.round(sum);
});

const previewPrice = computed(() => recipeMenu.value?.price ?? 0);
const previewProfit = computed(
    () => previewPrice.value - previewCogsRounded.value,
);
const previewMargin = computed(() =>
    previewPrice.value > 0
        ? Math.round((previewProfit.value / previewPrice.value) * 1000) / 10
        : 0,
);
</script>

<template>
    <Head title="Menu & Resep BOM" />
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    Katalog Menu & Resep BOM
                </h1>
                <p class="text-sm text-muted-foreground">
                    Kelola harga jual, modifier, ketersediaan, serta HPP dan Gross Margin berbasis resep.
                </p>
            </div>
            <Button
                @click="openCreateMenu"
                class="min-h-11 gap-2"
                :disabled="categories.length === 0"
            >
                <Plus class="size-4" />
                Tambah Menu
            </Button>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="relative min-w-64 flex-1">
                <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    :model-value="search"
                    @update:model-value="
                        search = $event;
                        applyFilters($event, selectedCategory);
                    "
                    placeholder="Cari nama menu..."
                    class="pl-9"
                />
            </div>
            <select
                :value="selectedCategory"
                @change="
                    selectedCategory = ($event.target as HTMLSelectElement).value;
                    applyFilters(search, selectedCategory);
                "
                class="h-10 rounded-md border border-input bg-card px-3 text-sm"
            >
                <option value="">Semua Kategori</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                </option>
            </select>
        </div>

        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            <div
                v-for="item in menus"
                :key="item.id"
                class="flex flex-col overflow-hidden rounded-2xl border bg-card shadow-xs"
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

                    <div class="absolute top-3 left-3 flex flex-wrap gap-1.5">
                        <Badge
                            v-if="item.badge === 'best_seller'"
                            class="bg-crema text-white"
                        >
                            Best Seller
                        </Badge>
                        <Badge
                            v-if="item.badge === 'new'"
                            class="bg-espresso text-oat"
                        >
                            New
                        </Badge>
                    </div>

                    <div class="absolute top-3 right-3">
                        <span
                            :class="
                                cn(
                                    'rounded-full px-2.5 py-1 text-xs font-semibold shadow-xs',
                                    item.effective_available
                                        ? 'bg-emerald-600 text-white'
                                        : 'bg-red-700 text-white',
                                )
                            "
                        >
                            {{ item.effective_available ? 'Tersedia' : 'HABIS' }}
                        </span>
                    </div>
                </div>

                <div class="flex flex-1 flex-col gap-3 p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-xs font-medium text-muted-foreground">
                                {{ item.category_name }}
                            </p>
                            <h2 class="text-base font-bold">{{ item.name }}</h2>
                        </div>
                        <p class="font-money text-base font-bold text-primary">
                            {{ formatRupiah(item.price) }}
                        </p>
                    </div>

                    <p
                        v-if="!item.effective_available && item.unavailable_reason"
                        class="rounded-lg bg-red-50 px-2.5 py-1.5 text-xs font-medium text-red-800"
                    >
                        {{ item.unavailable_reason }}
                    </p>

                    <div class="grid grid-cols-3 gap-2 rounded-xl bg-muted/60 p-2.5 text-xs">
                        <div>
                            <p class="text-muted-foreground">HPP (BOM)</p>
                            <p class="font-money font-semibold">
                                {{ formatRupiah(item.cogs) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Profit</p>
                            <p class="font-money font-semibold">
                                {{ formatRupiah(item.gross_profit) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Margin</p>
                            <p class="font-money font-semibold text-emerald-700">
                                {{ item.gross_margin_percent }}%
                            </p>
                        </div>
                    </div>

                    <div class="mt-auto flex flex-wrap items-center gap-2 pt-2">
                        <Button
                            variant="outline"
                            size="sm"
                            class="flex-1 gap-1.5"
                            @click="openRecipeEditor(item)"
                        >
                            <FlaskConical class="size-3.5" />
                            Resep ({{ item.recipe_items.length }})
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            @click="toggleAvailability(item.id)"
                            title="Toggle Sold Out Manual"
                        >
                            <Power
                                :class="
                                    cn(
                                        'size-4',
                                        item.is_available
                                            ? 'text-emerald-700'
                                            : 'text-red-700',
                                    )
                                "
                            />
                        </Button>
                        <Button variant="outline" size="sm" @click="openEditMenu(item)">
                            <Edit2 class="size-4" />
                        </Button>
                        <Button variant="outline" size="sm" @click="deleteMenu(item)">
                            <Trash2 class="size-4 text-destructive" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Dialog
        :open="menuDialogOpen"
        @close="menuDialogOpen = false"
        class="max-h-[90vh] overflow-y-auto sm:max-w-lg"
    >
        <div class="mb-4">
            <h2 class="text-lg font-bold">
                {{ editingMenu ? 'Edit Menu' : 'Tambah Menu Baru' }}
            </h2>
        </div>
        <form @submit.prevent="submitMenu" class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <Label for="menu-cat">Kategori</Label>
                    <select
                        id="menu-cat"
                        v-model="formState.category_id"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                            {{ cat.name }}
                        </option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <Label for="menu-badge">Label</Label>
                    <select
                        id="menu-badge"
                        v-model="formState.badge"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option value="">Tanpa Label</option>
                        <option value="best_seller">Best Seller</option>
                        <option value="new">New</option>
                    </select>
                </div>
            </div>

            <div class="space-y-1.5">
                <Label for="menu-name">Nama Menu</Label>
                <Input id="menu-name" v-model="formState.name" required />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <Label for="menu-price">Harga Jual (Rp)</Label>
                    <Input
                        id="menu-price"
                        type="number"
                        min="0"
                        :model-value="formState.price"
                        @update:model-value="formState.price = Number($event)"
                        required
                    />
                </div>
                <div class="space-y-1.5">
                    <Label for="menu-sort">Urutan</Label>
                    <Input
                        id="menu-sort"
                        type="number"
                        min="0"
                        :model-value="formState.sort_order"
                        @update:model-value="formState.sort_order = Number($event)"
                    />
                </div>
            </div>

            <div class="space-y-1.5">
                <Label for="menu-desc">Deskripsi</Label>
                <textarea
                    id="menu-desc"
                    rows="2"
                    v-model="formState.description"
                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                />
            </div>

            <div class="space-y-1.5">
                <Label for="menu-image">Foto Menu (4:3, maks 2MB)</Label>
                <input
                    id="menu-image"
                    type="file"
                    accept="image/png,image/jpeg,image/webp"
                    @change="
                        formState.image =
                            ($event.target as HTMLInputElement).files?.[0] ?? null
                    "
                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                />
                <label
                    v-if="editingMenu?.image_url"
                    class="flex items-center gap-2 text-xs text-muted-foreground"
                >
                    <input type="checkbox" v-model="formState.remove_image" />
                    Hapus foto saat ini
                </label>
            </div>

            <div class="space-y-2">
                <Label>Grup Modifier Terkait</Label>
                <div class="grid grid-cols-2 gap-2">
                    <label
                        v-for="group in modifierGroups"
                        :key="group.id"
                        class="flex items-center gap-2 rounded-lg border px-3 py-2 text-xs"
                    >
                        <input
                            type="checkbox"
                            :checked="formState.modifier_group_ids.includes(group.id)"
                            @change="toggleModifierGroup(group.id)"
                        />
                        <span>{{ group.name }}</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" v-model="formState.is_available" />
                    Tersedia
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" v-model="formState.is_active" />
                    Aktif di Katalog
                </label>
            </div>

            <div class="mt-4 flex justify-end gap-2">
                <Button variant="outline" @click="menuDialogOpen = false">
                    Batal
                </Button>
                <Button type="submit" :disabled="submittingMenu">
                    Simpan Menu
                </Button>
            </div>
        </form>
    </Dialog>

    <Dialog
        :open="recipeMenu !== null"
        @close="recipeMenu = null"
        class="max-h-[90vh] overflow-y-auto sm:max-w-xl"
    >
        <div class="mb-4">
            <h2 class="text-lg font-bold">
                Editor Resep BOM — {{ recipeMenu?.name }}
            </h2>
        </div>
        <form @submit.prevent="saveRecipe" class="space-y-4">
            <div class="grid grid-cols-3 gap-3 rounded-xl border bg-muted/50 p-3 text-xs">
                <div>
                    <p class="text-muted-foreground">Harga Jual</p>
                    <p class="font-money text-sm font-bold">
                        {{ formatRupiah(previewPrice) }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">Estimasi HPP</p>
                    <p class="font-money text-sm font-bold text-crema">
                        {{ formatRupiah(previewCogsRounded) }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">Gross Margin</p>
                    <p class="font-money text-sm font-bold text-emerald-700">
                        {{ previewMargin }}% ({{ formatRupiah(previewProfit) }})
                    </p>
                </div>
            </div>

            <div class="space-y-2.5">
                <div
                    v-for="(row, index) in recipeRows"
                    :key="`${index}-${row.ingredient_id}`"
                    class="flex items-center gap-2"
                >
                    <select
                        :value="row.ingredient_id"
                        @change="
                            row.ingredient_id = Number(
                                ($event.target as HTMLSelectElement).value,
                            )
                        "
                        class="h-10 flex-1 rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option v-for="opt in ingredients" :key="opt.id" :value="opt.id">
                            {{ opt.name }} ({{ opt.unit }}) —
                            {{ formatRupiah(opt.cost_per_unit) }}/{{ opt.unit }}
                        </option>
                    </select>
                    <Input
                        type="number"
                        step="0.001"
                        min="0.001"
                        v-model="row.quantity"
                        class="font-money w-28"
                        required
                    />
                    <span class="w-8 text-xs text-muted-foreground">
                        {{
                            ingredients.find((i) => i.id === row.ingredient_id)
                                ?.unit ?? ''
                        }}
                    </span>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="recipeRows.splice(index, 1)"
                    >
                        <Trash2 class="size-4 text-destructive" />
                    </Button>
                </div>
            </div>

            <Button
                variant="outline"
                size="sm"
                @click="addRecipeRow"
                class="gap-1.5"
            >
                <Plus class="size-4" />
                Tambah Bahan Baku
            </Button>

            <div class="mt-4 flex justify-end gap-2">
                <Button variant="outline" @click="recipeMenu = null">Batal</Button>
                <Button type="submit" :disabled="savingRecipe">
                    Simpan Resep BOM
                </Button>
            </div>
        </form>
    </Dialog>
</template>
