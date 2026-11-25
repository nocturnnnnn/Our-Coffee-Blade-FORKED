<script setup lang="ts">
import { Delete } from 'lucide-vue-next';
import { computed } from 'vue';
import Button from '@/components/ui/Button.vue';
import { formatRupiah } from '@/lib/format';
import { cn } from '@/lib/utils';

const props = defineProps<{
    totalAmount: number;
    modelValue: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const QUICK_AMOUNTS = [20000, 50000, 100000, 200000];
const KEYS = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '000', '0'];

const cashReceived = computed(() => Number(props.modelValue) || 0);
const change = computed(() => cashReceived.value - props.totalAmount);

function append(chunk: string) {
    const next = props.modelValue === '0' ? chunk : `${props.modelValue}${chunk}`;
    if (next.length > 9) {
        return;
    }
    emit('update:modelValue', next);
}

function backspace() {
    emit('update:modelValue', props.modelValue.slice(0, -1));
}
</script>

<template>
    <div class="space-y-3">
        <div class="grid grid-cols-2 gap-2 rounded-xl border bg-muted/50 p-3 text-xs">
            <div>
                <p class="text-muted-foreground">Uang Diterima</p>
                <p class="font-money text-base font-bold text-foreground">
                    {{ formatRupiah(cashReceived) }}
                </p>
            </div>
            <div class="text-right">
                <p class="text-muted-foreground">Kembalian</p>
                <p
                    :class="
                        cn(
                            'font-money text-base font-bold',
                            change < 0 ? 'text-red-700' : 'text-emerald-700',
                        )
                    "
                >
                    {{
                        change < 0
                            ? `Kurang ${formatRupiah(Math.abs(change))}`
                            : formatRupiah(change)
                    }}
                </p>
            </div>
        </div>

        <div class="flex flex-wrap gap-1.5">
            <Button
                variant="secondary"
                @click="emit('update:modelValue', String(totalAmount))"
                class="min-h-12 flex-1 text-xs font-bold"
            >
                Uang Pas
            </Button>
            <Button
                v-for="preset in QUICK_AMOUNTS"
                :key="preset"
                variant="outline"
                @click="emit('update:modelValue', String(preset))"
                class="font-money min-h-12 px-2.5 text-xs font-semibold"
            >
                {{ preset / 1000 }}k
            </Button>
        </div>

        <div class="grid grid-cols-3 gap-2">
            <Button
                v-for="key in KEYS"
                :key="key"
                variant="outline"
                @click="append(key)"
                class="font-money min-h-[60px] min-w-[60px] rounded-xl text-lg font-bold"
            >
                {{ key }}
            </Button>
            <Button
                variant="outline"
                @click="backspace"
                class="min-h-[60px] min-w-[60px] rounded-xl"
                aria-label="Hapus digit"
            >
                <Delete class="size-5" />
            </Button>
        </div>

        <Button
            variant="ghost"
            @click="emit('update:modelValue', '')"
            class="min-h-12 w-full text-xs text-muted-foreground"
        >
            Bersihkan Nominal
        </Button>
    </div>
</template>
