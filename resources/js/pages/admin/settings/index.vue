<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Copy, ExternalLink, Plus, Power, RefreshCw, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import { toast } from '@/composables/useToast';
import { formatDateWib, formatTimeWib } from '@/lib/format';

type KioskDeviceRow = {
    id: number;
    name: string;
    device_token: string;
    pair_url: string;
    is_active: boolean;
    last_seen_at: string | null;
    paired_by_name: string | null;
};

type SettingsProps = {
    settings: {
        cafe_name: string;
        cafe_address: string;
        cafe_phone: string;
        receipt_footer: string;
        kiosk_idle_timeout_seconds: number;
        kiosk_idle_warning_seconds: number;
        kiosk_confirmation_reset_seconds: number;
        qris_expiry_seconds: number;
    };
    devices: KioskDeviceRow[];
};

const props = defineProps<SettingsProps>();

const settingsForm = useForm({ ...props.settings });
const newDeviceName = ref('');

function saveSettings() {
    settingsForm.put('/admin/settings');
}

function handleAddDevice() {
    if (!newDeviceName.value.trim()) return;
    router.post(
        '/admin/settings/kiosk-devices',
        { name: newDeviceName.value.trim() },
        {
            onSuccess: () => {
                newDeviceName.value = '';
            },
        },
    );
}

function copyPairLink(url: string) {
    void navigator.clipboard?.writeText(url);
    toast.success('Tautan pairing Kiosk disalin ke clipboard.');
}

function toggleDevice(id: number) {
    router.patch(`/admin/settings/kiosk-devices/${id}/toggle`);
}

function rotateDevice(id: number) {
    router.post(`/admin/settings/kiosk-devices/${id}/rotate-token`);
}

function deleteDevice(dev: KioskDeviceRow) {
    if (window.confirm(`Hapus perangkat "${dev.name}"?`)) {
        router.delete(`/admin/settings/kiosk-devices/${dev.id}`);
    }
}
</script>

<template>
    <Head title="Pengaturan & Pairing Kiosk" />
    <div class="space-y-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">
                Pengaturan Kafe & Pairing Kiosk
            </h1>
            <p class="text-sm text-muted-foreground">
                Atur identitas struk thermal, durasi keamanan Kiosk, dan perangkat Kiosk terpasang.
            </p>
        </div>

        <form
            @submit.prevent="saveSettings"
            class="space-y-5 rounded-2xl border bg-card p-6 shadow-xs"
        >
            <h2 class="text-base font-bold">
                Identitas Kafe & Parameter Operasional
            </h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <Label for="set-name">Nama Kafe</Label>
                    <Input id="set-name" v-model="settingsForm.cafe_name" required />
                </div>
                <div class="space-y-1.5">
                    <Label for="set-phone">Telepon</Label>
                    <Input id="set-phone" v-model="settingsForm.cafe_phone" />
                </div>
                <div class="space-y-1.5 sm:col-span-2">
                    <Label for="set-address">Alamat Struk</Label>
                    <Input id="set-address" v-model="settingsForm.cafe_address" />
                </div>
                <div class="space-y-1.5 sm:col-span-2">
                    <Label for="set-footer">Footer Struk</Label>
                    <Input id="set-footer" v-model="settingsForm.receipt_footer" />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-4">
                <div class="space-y-1.5">
                    <Label for="set-idle">Idle Timeout (detik)</Label>
                    <Input
                        id="set-idle"
                        type="number"
                        min="30"
                        :model-value="settingsForm.kiosk_idle_timeout_seconds"
                        @update:model-value="
                            settingsForm.kiosk_idle_timeout_seconds = Number($event)
                        "
                    />
                </div>
                <div class="space-y-1.5">
                    <Label for="set-warn">Peringatan Idle (detik)</Label>
                    <Input
                        id="set-warn"
                        type="number"
                        min="10"
                        :model-value="settingsForm.kiosk_idle_warning_seconds"
                        @update:model-value="
                            settingsForm.kiosk_idle_warning_seconds = Number($event)
                        "
                    />
                </div>
                <div class="space-y-1.5">
                    <Label for="set-reset">Reset Konfirmasi (detik)</Label>
                    <Input
                        id="set-reset"
                        type="number"
                        min="3"
                        :model-value="settingsForm.kiosk_confirmation_reset_seconds"
                        @update:model-value="
                            settingsForm.kiosk_confirmation_reset_seconds = Number($event)
                        "
                    />
                </div>
                <div class="space-y-1.5">
                    <Label for="set-qris">Masa Berlaku QRIS (detik)</Label>
                    <Input
                        id="set-qris"
                        type="number"
                        min="60"
                        :model-value="settingsForm.qris_expiry_seconds"
                        @update:model-value="
                            settingsForm.qris_expiry_seconds = Number($event)
                        "
                    />
                </div>
            </div>

            <Button
                type="submit"
                :disabled="settingsForm.processing"
                class="min-h-11 font-bold"
            >
                Simpan Pengaturan
            </Button>
        </form>

        <div class="space-y-4 rounded-2xl border bg-card p-6 shadow-xs">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold">
                        Perangkat Self-Ordering Kiosk
                    </h2>
                    <p class="text-xs text-muted-foreground">
                        Buka tautan pairing pada tablet/layar Kiosk untuk menanamkan token perangkat.
                    </p>
                </div>

                <form @submit.prevent="handleAddDevice" class="flex items-center gap-2">
                    <Input
                        v-model="newDeviceName"
                        placeholder="Nama perangkat (mis. Kiosk 2)"
                        class="w-56"
                    />
                    <Button type="submit" class="gap-1.5">
                        <Plus class="size-4" />
                        Tambah Kiosk
                    </Button>
                </form>
            </div>

            <div class="space-y-3">
                <div
                    v-for="dev in devices"
                    :key="dev.id"
                    class="flex flex-wrap items-center justify-between gap-4 rounded-xl border p-4"
                >
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="font-bold">{{ dev.name }}</p>
                            <Badge :variant="dev.is_active ? 'default' : 'destructive'">
                                {{ dev.is_active ? 'Aktif' : 'Nonaktif' }}
                            </Badge>
                        </div>
                        <p class="mt-1 font-mono text-xs text-muted-foreground">
                            Token: {{ dev.device_token }}
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            Terakhir aktif:
                            {{
                                dev.last_seen_at
                                    ? `${formatDateWib(dev.last_seen_at)} ${formatTimeWib(dev.last_seen_at)} WIB`
                                    : 'Belum pernah terhubung'
                            }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <Button
                            size="sm"
                            variant="outline"
                            @click="copyPairLink(dev.pair_url)"
                            class="gap-1.5"
                        >
                            <Copy class="size-3.5" />
                            Salin Link Pair
                        </Button>
                        <a :href="dev.pair_url" target="_blank" rel="noreferrer">
                            <Button size="sm" variant="outline" class="gap-1.5">
                                <ExternalLink class="size-3.5" />
                                Buka Kiosk
                            </Button>
                        </a>
                        <Button
                            size="sm"
                            variant="outline"
                            @click="toggleDevice(dev.id)"
                            title="Aktif / Nonaktifkan"
                        >
                            <Power class="size-3.5" />
                        </Button>
                        <Button
                            size="sm"
                            variant="outline"
                            @click="rotateDevice(dev.id)"
                            title="Rotasi Token"
                        >
                            <RefreshCw class="size-3.5" />
                        </Button>
                        <Button
                            size="sm"
                            variant="outline"
                            @click="deleteDevice(dev)"
                        >
                            <Trash2 class="size-3.5 text-destructive" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
