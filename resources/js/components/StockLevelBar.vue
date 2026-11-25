<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    currentStock: number;
    minStock: number;
    unit: string;
}>();

const isEmpty = computed(() => props.currentStock <= 0);
const isLow = computed(() => !isEmpty.value && props.currentStock <= props.minStock);
const targetMax = computed(() =>
    props.minStock > 0 ? props.minStock * 3 : Math.max(props.currentStock, 100),
);
const percent = computed(() =>
    Math.max(0, Math.min(100, Math.round((props.currentStock / targetMax.value) * 100))),
);

const statusLabel = computed(() =>
    isEmpty.value ? 'Habis' : isLow.value ? 'Menipis' : 'Aman',
);

const badgeClass = computed(() =>
    isEmpty.value
        ? 'bg-red-100 text-red-800 border-red-200'
        : isLow.value
          ? 'bg-amber-100 text-amber-900 border-amber-200'
          : 'bg-emerald-100 text-emerald-900 border-emerald-200',
);

const barClass = computed(() =>
    isEmpty.value ? 'bg-red-600' : isLow.value ? 'bg-amber-600' : 'bg-emerald-600',
);
</script>

<template>
    <div class="min-w-40 space-y-1.5">
        <div class="flex items-center justify-between gap-2 text-xs">
            <span class="font-money font-semibold text-foreground">
                {{ currentStock.toLocaleString('id-ID', { maximumFractionDigits: 2 }) }}
                {{ unit }}
            </span>
            <span
                :class="
                    cn(
                        'rounded-full border px-2 py-0.5 text-[11px] font-semibold',
                        badgeClass,
                    )
                "
            >
                {{ statusLabel }}
            </span>
        </div>
        <div class="h-2 w-full overflow-hidden rounded-full bg-secondary">
            <div
                :class="cn('h-full rounded-full transition-all', barClass)"
                :style="{ width: `${percent}%` }"
            />
        </div>
        <p class="font-money text-[11px] text-muted-foreground">
            Min:
            {{ minStock.toLocaleString('id-ID', { maximumFractionDigits: 2 }) }}
            {{ unit }}
        </p>
    </div>
</template>
