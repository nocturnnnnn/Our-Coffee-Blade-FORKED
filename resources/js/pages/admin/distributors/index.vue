<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Edit2, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';

type DistributorItem = {
    id: number;
    name: string;
    contact_person: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
    notes: string | null;
    is_active: boolean;
    ingredients_count: number;
};

defineProps<{
    distributors: DistributorItem[];
}>();

const dialogOpen = ref(false);
const editing = ref<DistributorItem | null>(null);

const form = useForm({
    name: '',
    contact_person: '',
    phone: '',
    email: '',
    address: '',
    notes: '',
    is_active: true,
});

function openCreate() {
    editing.value = null;
    form.name = '';
    form.contact_person = '';
    form.phone = '';
    form.email = '';
    form.address = '';
    form.notes = '';
    form.is_active = true;
    dialogOpen.value = true;
}

function openEdit(dist: DistributorItem) {
    editing.value = dist;
    form.name = dist.name;
    form.contact_person = dist.contact_person ?? '';
    form.phone = dist.phone ?? '';
    form.email = dist.email ?? '';
    form.address = dist.address ?? '';
    form.notes = dist.notes ?? '';
    form.is_active = dist.is_active;
    dialogOpen.value = true;
}

function submit() {
    if (editing.value) {
        form.put(`/admin/distributors/${editing.value.id}`, {
            onSuccess: () => {
                dialogOpen.value = false;
            },
        });
    } else {
        form.post('/admin/distributors', {
            onSuccess: () => {
                dialogOpen.value = false;
            },
        });
    }
}

function handleDelete(dist: DistributorItem) {
    if (window.confirm(`Hapus distributor "${dist.name}"?`)) {
        router.delete(`/admin/distributors/${dist.id}`);
    }
}
</script>

<template>
    <Head title="Distributor" />
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Master Distributor</h1>
                <p class="text-sm text-muted-foreground">
                    Kelola pemasok biji kopi, susu, sirup, dan pastry untuk pencatatan Stock In.
                </p>
            </div>
            <Button @click="openCreate" class="min-h-11 gap-2">
                <Plus class="size-4" />
                Tambah Distributor
            </Button>
        </div>

        <div class="overflow-hidden rounded-2xl border bg-card shadow-xs">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-muted/60 text-xs text-muted-foreground uppercase">
                    <tr>
                        <th class="px-4 py-3">Nama Pemasok</th>
                        <th class="px-4 py-3">Kontak PIC</th>
                        <th class="px-4 py-3">Telepon / Email</th>
                        <th class="px-4 py-3">Bahan Terkait</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="dist in distributors" :key="dist.id" class="hover:bg-muted/30">
                        <td class="px-4 py-3">
                            <p class="font-semibold">{{ dist.name }}</p>
                            <p v-if="dist.address" class="text-xs text-muted-foreground">
                                {{ dist.address }}
                            </p>
                        </td>
                        <td class="px-4 py-3">{{ dist.contact_person ?? '-' }}</td>
                        <td class="px-4 py-3 text-xs">
                            <p>{{ dist.phone ?? '-' }}</p>
                            <p class="text-muted-foreground">{{ dist.email ?? '' }}</p>
                        </td>
                        <td class="font-money px-4 py-3">{{ dist.ingredients_count }} bahan</td>
                        <td class="px-4 py-3">
                            <Badge :variant="dist.is_active ? 'default' : 'secondary'">
                                {{ dist.is_active ? 'Aktif' : 'Nonaktif' }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex items-center gap-2">
                                <Button variant="outline" size="sm" @click="openEdit(dist)">
                                    <Edit2 class="size-4" />
                                </Button>
                                <Button variant="outline" size="sm" @click="handleDelete(dist)">
                                    <Trash2 class="size-4 text-destructive" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <Dialog :open="dialogOpen" @close="dialogOpen = false">
        <div class="mb-4">
            <h2 class="text-lg font-bold">
                {{ editing ? 'Edit Distributor' : 'Tambah Distributor' }}
            </h2>
        </div>
        <form @submit.prevent="submit" class="space-y-4">
            <div class="space-y-1.5">
                <Label for="dist-name">Nama Distributor</Label>
                <Input id="dist-name" v-model="form.name" required />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <Label for="dist-pic">Nama PIC</Label>
                    <Input id="dist-pic" v-model="form.contact_person" />
                </div>
                <div class="space-y-1.5">
                    <Label for="dist-phone">Telepon</Label>
                    <Input id="dist-phone" v-model="form.phone" />
                </div>
            </div>
            <div class="space-y-1.5">
                <Label for="dist-email">Email</Label>
                <Input id="dist-email" type="email" v-model="form.email" />
            </div>
            <div class="space-y-1.5">
                <Label for="dist-address">Alamat</Label>
                <textarea
                    id="dist-address"
                    rows="2"
                    v-model="form.address"
                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                />
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" v-model="form.is_active" />
                Distributor Aktif
            </label>
            <div class="mt-4 flex justify-end gap-2">
                <Button variant="outline" @click="dialogOpen = false">Batal</Button>
                <Button type="submit" :disabled="form.processing">Simpan</Button>
            </div>
        </form>
    </Dialog>
</template>
