<script setup lang="ts">
import { Delete, ShieldCheck } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import Button from '@/components/ui/Button.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';

const props = withDefaults(
    defineProps<{
        open: boolean;
        mode: 'discount' | 'void';
        maxDiscount?: number;
        errorMessage?: string | null;
        submitting?: boolean;
    }>(),
    {
        maxDiscount: 0,
        errorMessage: null,
        submitting: false,
    },
);

const emit = defineEmits<{
    (e: 'close'): void;
    (
        e: 'confirm',
        payload: { pin: string; reason: string; discountAmount?: number },
    ): void;
}>();

const PAD = ['1', '2', '3', '4', '5', '6', '7', '8', '9'];

const pin = ref('');
const reason = ref('');
const discountAmount = ref('');

function resetAndClose() {
    pin.value = '';
    reason.value = '';
    discountAmount.value = '';
    emit('close');
}

const canSubmit = computed(
    () =>
        pin.value.length === 6 &&
        reason.value.trim().length > 0 &&
        (props.mode !== 'discount' || (Number(discountAmount.value) || 0) > 0) &&
        !props.submitting,
);

function handleSubmit() {
    if (pin.value.length !== 6 || !reason.value.trim()) {
        return;
    }
    emit('confirm', {
        pin: pin.value,
        reason: reason.value.trim(),
        discountAmount:
            props.mode === 'discount'
                ? Math.max(
                      0,
                      Math.min(
                          props.maxDiscount,
                          Number(discountAmount.value) || 0,
                      ),
                  )
                : undefined,
    });
}
</script>

<template>
    <Dialog :open="open" @close="resetAndClose" class="sm:max-w-md">
        <div class="mb-4">
            <h2 class="flex items-center gap-2 text-lg font-bold">
                <ShieldCheck class="size-5 text-primary" />
                {{
                    mode === 'void'
                        ? 'Otorisasi Void Pesanan'
                        : 'Otorisasi Diskon Manual'
                }}
            </h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Wajib menyertakan alasan dan PIN Supervisor/Admin 6 digit.
            </p>
        </div>

        <div class="space-y-4">
            <div v-if="mode === 'discount'" class="space-y-1.5">
                <Label for="sup-discount">
                    Nominal Diskon (Rp, maks
                    {{ maxDiscount.toLocaleString('id-ID') }})
                </Label>
                <Input
                    id="sup-discount"
                    v-model="discountAmount"
                    type="number"
                    min="1"
                    :max="maxDiscount"
                    placeholder="Contoh: 10000"
                    class="font-money h-12"
                />
            </div>

            <div class="space-y-1.5">
                <Label for="sup-reason">
                    {{ mode === 'void' ? 'Alasan Void' : 'Alasan Diskon' }}
                </Label>
                <Input
                    id="sup-reason"
                    v-model="reason"
                    :placeholder="
                        mode === 'void'
                            ? 'Contoh: Pelanggan salah pesan'
                            : 'Contoh: Promo karyawan / kompensasi'
                    "
                    class="h-12"
                />
            </div>

            <div class="space-y-2">
                <Label>PIN Admin 6 Digit</Label>
                <div class="flex justify-center gap-2">
                    <div
                        v-for="i in [0, 1, 2, 3, 4, 5]"
                        :key="i"
                        class="font-money flex size-10 items-center justify-center rounded-xl border-2 bg-muted text-lg font-bold"
                    >
                        {{ pin[i] ? '•' : '' }}
                    </div>
                </div>
            </div>

            <p
                v-if="errorMessage"
                class="rounded-lg bg-red-50 px-3 py-2 text-center text-xs font-semibold text-red-700"
            >
                {{ errorMessage }}
            </p>

            <div class="grid grid-cols-3 gap-2">
                <Button
                    v-for="digit in PAD"
                    :key="digit"
                    variant="outline"
                    @click="if (pin.length < 6) pin += digit;"
                    class="font-money min-h-[60px] min-w-[60px] rounded-xl text-lg font-bold"
                >
                    {{ digit }}
                </Button>
                <Button
                    variant="outline"
                    @click="pin = pin.slice(0, -1)"
                    class="min-h-[60px] min-w-[60px] rounded-xl"
                    aria-label="Hapus digit"
                >
                    <Delete class="size-5" />
                </Button>
                <Button
                    variant="outline"
                    @click="if (pin.length < 6) pin += '0';"
                    class="font-money min-h-[60px] min-w-[60px] rounded-xl text-lg font-bold"
                >
                    0
                </Button>
                <Button
                    variant="ghost"
                    @click="pin = ''"
                    class="min-h-[60px] min-w-[60px] rounded-xl text-xs"
                >
                    Reset
                </Button>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <Button variant="outline" @click="resetAndClose" class="min-h-12">
                Batal
            </Button>
            <Button
                :disabled="!canSubmit"
                @click="handleSubmit"
                class="min-h-12 font-bold"
            >
                {{ submitting ? 'Memverifikasi...' : 'Konfirmasi Otorisasi' }}
            </Button>
        </div>
    </Dialog>
</template>
