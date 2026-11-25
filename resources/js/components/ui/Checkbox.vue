<script setup lang="ts">
import { Check } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        modelValue?: boolean;
        id?: string;
        name?: string;
        disabled?: boolean;
        class?: string;
    }>(),
    {
        modelValue: false,
        disabled: false,
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
}>();
</script>

<template>
    <button
        type="button"
        role="checkbox"
        :id="id"
        :aria-checked="modelValue"
        :disabled="disabled"
        @click="!disabled && emit('update:modelValue', !modelValue)"
        :class="
            cn(
                'peer border-input focus-visible:border-ring focus-visible:ring-ring/50 flex size-4 shrink-0 items-center justify-center rounded-[4px] border shadow-xs transition-shadow outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50',
                modelValue
                    ? 'bg-primary text-primary-foreground border-primary'
                    : 'bg-transparent',
                props.class,
            )
        "
    >
        <Check v-if="modelValue" class="size-3.5" />
    </button>
    <input v-if="name" type="hidden" :name="name" :value="modelValue ? '1' : '0'" />
</template>
