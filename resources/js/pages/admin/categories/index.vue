<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Edit2, FolderPlus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';

type CategoryItem = {
    id: number;
    name: string;
    slug: string;
    icon: string | null;
    sort_order: number;
    is_active: boolean;
    menu_items_count: number;
};

const ICON_OPTIONS = [
    { value: 'sparkles', label: 'Sparkles (Signature)' },
    { value: 'coffee', label: 'Coffee' },
    { value: 'cup-soda', label: 'Cold Drink' },
    { value: 'croissant', label: 'Pastry' },
    { value: 'utensils', label: 'Food' },
];

const props = defineProps<{
    categories: CategoryItem[];
}>();

const dialogOpen = ref(false);
const editing = ref<CategoryItem | null>(null);

const form = useForm({
    name: '',
    icon: 'coffee',
    sort_order: 0,
    is_active: true,
});

function openCreate() {
    editing.value = null;
    form.name = '';
    form.icon = 'coffee';
    form.sort_order = props.categories.length + 1;
    form.is_active = true;
    form.clearErrors();
    dialogOpen.value = true;
}

function openEdit(category: CategoryItem) {
    editing.value = category;
    form.name = category.name;
    form.icon = category.icon ?? 'coffee';
    form.sort_order = category.sort_order;
    form.is_active = category.is_active;
    form.clearErrors();
    dialogOpen.value = true;
}

function submit() {
    if (editing.value) {
        form.put(`/admin/categories/${editing.value.id}`, {
            onSuccess: () => {
                dialogOpen.value = false;
            },
        });
    } else {
        form.post('/admin/categories', {
            onSuccess: () => {
                dialogOpen.value = false;
            },
        });
    }
}

function handleDelete(category: CategoryItem) {
    if (!window.confirm(`Hapus kategori "${category.name}"?`)) {
        return;
    }
    router.delete(`/admin/categories/${category.id}`);
}
</script>

<template>
    <Head title="Kategori Menu" />
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Kategori Menu</h1>
                <p class="text-sm text-muted-foreground">
                    Atur urutan kategori pada Left Rail Kiosk dan POS Kasir.
                </p>
            </div>
            <Button @click="openCreate" class="min-h-11 gap-2">
                <FolderPlus class="size-4" />
                Tambah Kategori
            </Button>
        </div>

        <div class="overflow-hidden rounded-2xl border bg-card shadow-xs">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-muted/60 text-xs text-muted-foreground uppercase">
                    <tr>
                        <th class="px-4 py-3">Urutan</th>
                        <th class="px-4 py-3">Nama Kategori</th>
                        <th class="px-4 py-3">Ikon Rail</th>
                        <th class="px-4 py-3">Jumlah Menu</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="cat in categories" :key="cat.id" class="hover:bg-muted/30">
                        <td class="font-money px-4 py-3 font-semibold">
                            {{ cat.sort_order }}
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold">{{ cat.name }}</p>
                            <p class="text-xs text-muted-foreground">/{{ cat.slug }}</p>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ cat.icon ?? 'coffee' }}
                        </td>
                        <td class="font-money px-4 py-3">{{ cat.menu_items_count }} menu</td>
                        <td class="px-4 py-3">
                            <Badge :variant="cat.is_active ? 'default' : 'secondary'">
                                {{ cat.is_active ? 'Aktif' : 'Nonaktif' }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex items-center gap-2">
                                <Button variant="outline" size="sm" @click="openEdit(cat)">
                                    <Edit2 class="size-4" />
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="handleDelete(cat)"
                                    :disabled="cat.menu_items_count > 0"
                                >
                                    <Trash2 class="size-4 text-destructive" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="categories.length === 0">
                        <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
                            Belum ada kategori menu.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <Dialog :open="dialogOpen" @close="dialogOpen = false">
        <div class="mb-4">
            <h2 class="text-lg font-bold">
                {{ editing ? 'Edit Kategori' : 'Tambah Kategori' }}
            </h2>
        </div>
        <form @submit.prevent="submit" class="space-y-4">
            <div class="space-y-2">
                <Label for="cat-name">Nama Kategori</Label>
                <Input id="cat-name" v-model="form.name" required />
                <p v-if="form.errors.name" class="text-xs text-destructive">
                    {{ form.errors.name }}
                </p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <Label for="cat-icon">Ikon Left Rail</Label>
                    <select
                        id="cat-icon"
                        v-model="form.icon"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option
                            v-for="opt in ICON_OPTIONS"
                            :key="opt.value"
                            :value="opt.value"
                        >
                            {{ opt.label }}
                        </option>
                    </select>
                </div>
                <div class="space-y-2">
                    <Label for="cat-sort">Urutan</Label>
                    <Input
                        id="cat-sort"
                        type="number"
                        min="0"
                        :model-value="form.sort_order"
                        @update:model-value="form.sort_order = Number($event)"
                    />
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm font-medium">
                <input type="checkbox" v-model="form.is_active" />
                Tampilkan di Kiosk & POS
            </label>

            <div class="mt-4 flex justify-end gap-2">
                <Button variant="outline" @click="dialogOpen = false">Batal</Button>
                <Button type="submit" :disabled="form.processing">Simpan</Button>
            </div>
        </form>
    </Dialog>
</template>
