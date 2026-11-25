<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Coffee } from 'lucide-vue-next';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        href="/"
                        class="flex flex-col items-center gap-3 font-medium transition-transform hover:scale-105"
                    >
                        <div class="flex size-14 items-center justify-center rounded-2xl bg-sidebar-primary text-sidebar-primary-foreground shadow-md ring-4 ring-sidebar-primary/20">
                            <Coffee class="size-8" />
                        </div>
                        <div class="text-center">
                            <span class="text-lg font-bold tracking-tight text-foreground">
                                Our Coffee
                            </span>
                            <p class="text-xs text-muted-foreground">
                                Cafe Operations Platform
                            </p>
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>

                    <div class="space-y-1.5 text-center">
                        <h1 class="text-xl font-bold tracking-tight text-foreground">
                            {{ title }}
                        </h1>
                        <p class="text-center text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
