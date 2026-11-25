<script setup lang="ts">
import { Delete, ShieldAlert } from 'lucide-vue-next';
import { ref } from 'vue';
import Button from '@/components/ui/Button.vue';
import Dialog from '@/components/ui/Dialog.vue';
import { jsonRequest } from '@/lib/http';

defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const KEYPAD = ['1', '2', '3', '4', '5', '6', '7', '8', '9'];

const pin = ref('');
const error = ref<string | null>(null);
const submitting = ref(false);

function handleDigit(digit: string) {
    if (submitting.value || pin.value.length >= 6) {
        return;
    }
    error.value = null;
    pin.value += digit;
}

function handleBackspace() {
    if (submitting.value) {
        return;
    }
    error.value = null;
    pin.value = pin.value.slice(0, -1);
}

async function handleVerify() {
    if (pin.value.length !== 6 || submitting.value) {
        return;
    }
    submitting.value = true;
    error.value = null;

    try {
        const res = await jsonRequest<{ redirect?: string; message?: string }>(
            '/kiosk/exit',
            {
                method: 'POST',
                body: { pin: pin.value },
            },
        );

        if (res.ok && res.data.redirect) {
            window.location.href = res.data.redirect;
            return;
        }

        error.value = res.data.message ?? 'PIN Admin tidak valid.';
        pin.value = '';
    } catch {
        error.value = 'Gagal memverifikasi PIN.';
    } finally {
        submitting.value = false;
    }
}

function handleClose() {
    pin.value = '';
    error.value = null;
    emit('close');
}
</script>

<template>
    <Dialog :open="open" @close="handleClose" class="sm:max-w-sm">
        <div class="mb-4">
            <h2 class="flex items-center gap-2 text-lg font-bold">
                <ShieldAlert class="size-5 text-primary" />
                Keluar Mode Kiosk
            </h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Masukkan PIN Admin 6 digit untuk kembali ke halaman login staf.
            </p>
        </div>

        <div class="space-y-4">
            <div class="flex justify-center gap-2.5 py-2">
                <div
                    v-for="idx in [0, 1, 2, 3, 4, 5]"
                    :key="idx"
                    class="font-money flex size-11 items-center justify-center rounded-xl border-2 border-border bg-muted text-xl font-bold"
                >
                    {{ pin[idx] ? '•' : '' }}
                </div>
            </div>

            <p
                v-if="error"
                class="rounded-lg bg-red-50 px-3 py-2 text-center text-xs font-semibold text-red-700"
            >
                {{ error }}
            </p>

            <div class="grid grid-cols-3 gap-2.5">
                <Button
                    v-for="digit in KEYPAD"
                    :key="digit"
                    variant="outline"
                    @click="handleDigit(digit)"
                    class="font-money min-h-16 min-w-16 rounded-xl text-xl font-bold"
                >
                    {{ digit }}
                </Button>
                <Button
                    variant="outline"
                    @click="handleBackspace"
                    class="min-h-16 min-w-16 rounded-xl"
                    aria-label="Hapus digit"
                >
                    <Delete class="size-6" />
                </Button>
                <Button
                    variant="outline"
                    @click="handleDigit('0')"
                    class="font-money min-h-16 min-w-16 rounded-xl text-xl font-bold"
                >
                    0
                </Button>
                <Button
                    :disabled="pin.length !== 6 || submitting"
                    @click="handleVerify"
                    class="min-h-16 min-w-16 rounded-xl text-sm font-bold"
                >
                    Masuk
                </Button>
            </div>
        </div>
    </Dialog>
</template>
