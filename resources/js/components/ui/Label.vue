<script setup lang="ts">
import { cn } from '@/lib/utils';

const props = defineProps<{
    for?: string;
    class?: string;
}>();
</script>

<template>
    <label
        :for="props.for"
        data-slot="label"
        :class="
            cn(
                'text-sm leading-none font-medium select-none peer-disabled:cursor-not-allowed peer-disabled:opacity-50',
                props.class,
            )
        "
    >
        <slot />
    </label>
</template>
