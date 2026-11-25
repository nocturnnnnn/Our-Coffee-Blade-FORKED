<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Coffee } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const name = computed(() => (page.props.name as string) || 'Our Coffee');
</script>

<template>
    <div class="flex aspect-square size-9 items-center justify-center rounded-xl bg-sidebar-primary text-sidebar-primary-foreground shadow-xs">
        <Coffee class="size-5" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-bold text-foreground">
            {{ name }}
        </span>
        <span class="truncate text-xs text-muted-foreground">
            Cafe Operations
        </span>
    </div>
</template>
