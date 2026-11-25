<script setup lang="ts">
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { type Appearance, useAppearance } from '@/composables/useAppearance';
import { cn } from '@/lib/utils';

defineProps<{ class?: string }>();

const { appearance, updateAppearance } = useAppearance();

const tabs: { value: Appearance; icon: typeof Sun; label: string }[] = [
    { value: 'light', icon: Sun, label: 'Light' },
    { value: 'dark', icon: Moon, label: 'Dark' },
    { value: 'system', icon: Monitor, label: 'System' },
];
</script>

<template>
    <div
        :class="
            cn(
                'inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800',
                $props.class,
            )
        "
    >
        <button
            v-for="tab in tabs"
            :key="tab.value"
            type="button"
            @click="updateAppearance(tab.value)"
            :class="
                cn(
                    'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
                    appearance === tab.value
                        ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                        : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
                )
            "
        >
            <component :is="tab.icon" class="-ml-1 h-4 w-4" />
            <span class="ml-1.5 text-sm">{{ tab.label }}</span>
        </button>
    </div>
</template>
