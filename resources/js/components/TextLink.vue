<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { cn, toUrl, type UrlTarget } from '@/lib/utils';

defineProps<{
    href: UrlTarget;
    class?: string;
    tabindex?: number;
}>();
</script>

<template>
    <Link
        :href="toUrl(href)"
        :tabindex="tabindex"
        :class="
            cn(
                'text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500',
                $props.class,
            )
        "
    >
        <slot />
    </Link>
</template>
