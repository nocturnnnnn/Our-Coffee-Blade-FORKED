<script setup lang="ts">
import { cn } from '@/lib/utils';

const props = defineProps<{ class?: string }>();
</script>

<template>
    <div
        role="separator"
        data-slot="separator"
        :class="cn('bg-border shrink-0 h-px w-full', props.class)"
    />
</template>
