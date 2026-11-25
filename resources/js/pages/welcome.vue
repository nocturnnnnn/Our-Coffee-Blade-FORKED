<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Coffee } from 'lucide-vue-next';
</script>

<template>
    <Head title="Our Coffee" />
    <div class="flex min-h-screen flex-col items-center justify-center bg-background p-6 text-foreground">
        <div class="flex flex-col items-center gap-4 text-center">
            <div class="flex size-16 items-center justify-center rounded-2xl bg-sidebar-primary text-sidebar-primary-foreground shadow-md">
                <Coffee class="size-9" />
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight">Our Coffee</h1>
            <p class="max-w-md text-sm text-muted-foreground">
                Platform Operasional Kafe Modern — Self-Ordering Kiosk, POS Kasir, Kitchen Display System, dan Backoffice Admin.
            </p>
            <div class="mt-4 flex gap-3">
                <Link
                    href="/login"
                    class="rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90"
                >
                    Masuk ke Sistem
                </Link>
            </div>
        </div>
    </div>
</template>
