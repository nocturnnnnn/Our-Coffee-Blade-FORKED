<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Edit2, KeyRound, UserPlus } from 'lucide-vue-next';
import { ref } from 'vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import type { UserRole } from '@/types/auth';

type StaffMember = {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    role_label: string;
    is_active: boolean;
    has_pin: boolean;
    created_at: string | null;
};

defineProps<{
    staff: StaffMember[];
}>();

const dialogOpen = ref(false);
const editing = ref<StaffMember | null>(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'kasir' as UserRole,
    pin: '',
    is_active: true,
});

const pinTarget = ref<StaffMember | null>(null);
const pinForm = useForm({
    pin: '',
});

function openCreate() {
    editing.value = null;
    form.name = '';
    form.email = '';
    form.password = '';
    form.role = 'kasir';
    form.pin = '';
    form.is_active = true;
    form.clearErrors();
    dialogOpen.value = true;
}

function openEdit(member: StaffMember) {
    editing.value = member;
    form.name = member.name;
    form.email = member.email;
    form.password = '';
    form.role = member.role;
    form.pin = '';
    form.is_active = member.is_active;
    form.clearErrors();
    dialogOpen.value = true;
}

function submitStaff() {
    if (editing.value) {
        form.put(`/admin/staff/${editing.value.id}`, {
            onSuccess: () => {
                dialogOpen.value = false;
            },
        });
    } else {
        form.post('/admin/staff', {
            onSuccess: () => {
                dialogOpen.value = false;
            },
        });
    }
}

function submitPin() {
    if (!pinTarget.value) return;
    pinForm.patch(`/admin/staff/${pinTarget.value.id}/pin`, {
        onSuccess: () => {
            pinTarget.value = null;
        },
    });
}
</script>

<template>
    <Head title="Karyawan & PIN" />
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    Manajemen Karyawan & PIN
                </h1>
                <p class="text-sm text-muted-foreground">
                    Kelola akun staf (Admin, Kasir, Barista) serta PIN otorisasi 6-digit.
                </p>
            </div>
            <Button @click="openCreate" class="min-h-11 gap-2">
                <UserPlus class="size-4" />
                Tambah Staf
            </Button>
        </div>

        <div class="overflow-hidden rounded-2xl border bg-card shadow-xs">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-muted/60 text-xs text-muted-foreground uppercase">
                    <tr>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">PIN Otorisasi</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="member in staff" :key="member.id">
                        <td class="px-4 py-3 font-semibold">{{ member.name }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ member.email }}</td>
                        <td class="px-4 py-3">
                            <Badge variant="outline">{{ member.role_label }}</Badge>
                        </td>
                        <td class="px-4 py-3">
                            <Badge :variant="member.has_pin ? 'default' : 'secondary'">
                                {{ member.has_pin ? 'PIN Terpasang' : 'Belum Ada PIN' }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3">
                            <Badge :variant="member.is_active ? 'default' : 'destructive'">
                                {{ member.is_active ? 'Aktif' : 'Nonaktif' }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="
                                        pinTarget = member;
                                        pinForm.pin = '';
                                        pinForm.clearErrors();
                                    "
                                    class="gap-1.5"
                                >
                                    <KeyRound class="size-3.5" />
                                    Atur PIN
                                </Button>
                                <Button variant="outline" size="sm" @click="openEdit(member)">
                                    <Edit2 class="size-4" />
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
                {{ editing ? 'Edit Akun Staf' : 'Tambah Staf Baru' }}
            </h2>
        </div>
        <form @submit.prevent="submitStaff" class="space-y-4">
            <div class="space-y-1.5">
                <Label for="st-name">Nama Lengkap</Label>
                <Input id="st-name" v-model="form.name" required />
            </div>
            <div class="space-y-1.5">
                <Label for="st-email">Email Login</Label>
                <Input id="st-email" type="email" v-model="form.email" required />
                <p v-if="form.errors.email" class="text-xs text-destructive">
                    {{ form.errors.email }}
                </p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <Label for="st-role">Role</Label>
                    <select
                        id="st-role"
                        v-model="form.role"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option value="admin">Admin</option>
                        <option value="kasir">Kasir</option>
                        <option value="barista">Barista</option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <Label for="st-pass">
                        {{ editing ? 'Password Baru (Opsional)' : 'Password' }}
                    </Label>
                    <Input
                        id="st-pass"
                        type="password"
                        minlength="8"
                        v-model="form.password"
                        :required="!editing"
                    />
                </div>
            </div>

            <div v-if="!editing" class="space-y-1.5">
                <Label for="st-pin">PIN 6 Digit (Opsional, wajib untuk Admin)</Label>
                <Input
                    id="st-pin"
                    maxlength="6"
                    v-model="form.pin"
                    placeholder="123456"
                    class="font-money"
                />
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" v-model="form.is_active" />
                Akun Aktif
            </label>

            <div class="mt-4 flex justify-end gap-2">
                <Button variant="outline" @click="dialogOpen = false">Batal</Button>
                <Button type="submit" :disabled="form.processing">Simpan</Button>
            </div>
        </form>
    </Dialog>

    <Dialog :open="pinTarget !== null" @close="pinTarget = null">
        <div class="mb-4">
            <h2 class="text-lg font-bold">
                Atur PIN Otorisasi — {{ pinTarget?.name }}
            </h2>
        </div>
        <form @submit.prevent="submitPin" class="space-y-4">
            <div class="space-y-1.5">
                <Label for="reset-pin">
                    PIN 6 Digit Baru (Kosongkan untuk menghapus PIN)
                </Label>
                <Input
                    id="reset-pin"
                    maxlength="6"
                    v-model="pinForm.pin"
                    placeholder="6 digit angka"
                    class="font-money"
                />
                <p v-if="pinForm.errors.pin" class="text-xs text-destructive">
                    {{ pinForm.errors.pin }}
                </p>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <Button variant="outline" @click="pinTarget = null">Batal</Button>
                <Button type="submit" :disabled="pinForm.processing">Simpan PIN</Button>
            </div>
        </form>
    </Dialog>
</template>
