<script setup lang="ts">
import { cva } from 'class-variance-authority';
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const badgeVariants = cva(
    'inline-flex items-center justify-center rounded-md border px-2 py-0.5 text-xs font-medium w-fit whitespace-nowrap shrink-0 [&>svg]:size-3 gap-1 [&>svg]:pointer-events-none transition-[color,box-shadow] overflow-hidden',
    {
        variants: {
            variant: {
                default:
                    'border-transparent bg-primary text-primary-foreground',
                secondary:
                    'border-transparent bg-secondary text-secondary-foreground',
                destructive:
                    'border-transparent bg-destructive text-white dark:bg-destructive/60',
                outline: 'text-foreground',
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    },
);

const props = withDefaults(
    defineProps<{
        variant?: 'default' | 'secondary' | 'destructive' | 'outline';
        class?: string;
    }>(),
    {
        variant: 'default',
    },
);

const classes = computed(() => cn(badgeVariants({ variant: props.variant }), props.class));
</script>

<template>
    <span data-slot="badge" :class="classes">
        <slot />
    </span>
</template>
