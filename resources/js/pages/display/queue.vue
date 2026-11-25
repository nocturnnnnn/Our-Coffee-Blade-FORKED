<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Coffee } from 'lucide-vue-next';
import { ref } from 'vue';
import { usePolling } from '@/composables/usePolling';
import { jsonRequest } from '@/lib/http';

type QueueTicket = {
    id: number;
    queue_number: string;
    customer_name: string | null;
    order_type: string;
};

const props = defineProps<{
    cafeName: string;
    preparing: QueueTicket[];
    ready: QueueTicket[];
}>();

const preparing = ref<QueueTicket[]>(props.preparing);
const ready = ref<QueueTicket[]>(props.ready);

async function refreshFeed() {
    try {
        const res = await jsonRequest<{
            preparing: QueueTicket[];
            ready: QueueTicket[];
        }>('/display/queue/feed');
        if (res.ok) {
            preparing.value = res.data.preparing;
            ready.value = res.data.ready;
        }
    } catch {
        // Silent retry
    }
}

usePolling(refreshFeed, 3000, true);
</script>

<template>
    <Head :title="`Antrean — ${cafeName}`" />
    <div class="flex min-h-screen flex-col bg-espresso p-6 text-oat">
        <header class="mb-6 flex items-center justify-between border-b border-white/10 pb-4">
            <div class="flex items-center gap-3">
                <div class="flex size-12 items-center justify-center rounded-2xl bg-crema text-white">
                    <Coffee class="size-7" />
                </div>
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight">{{ cafeName }}</h1>
                    <p class="text-xs font-medium text-latte">
                        Status Antrean Pesanan Real-Time
                    </p>
                </div>
            </div>
        </header>

        <div class="grid flex-1 gap-6 md:grid-cols-2">
            <section class="flex flex-col rounded-3xl border border-white/15 bg-white/5 p-6">
                <div class="mb-5 flex items-center justify-between border-b border-white/10 pb-3">
                    <h2 class="text-xl font-extrabold tracking-wide text-latte">
                        Sedang Disiapkan
                    </h2>
                    <span class="font-money rounded-full bg-white/10 px-3 py-1 text-sm font-bold">
                        {{ preparing.length }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <div
                        v-for="item in preparing"
                        :key="item.id"
                        class="rounded-2xl border border-white/15 bg-white/10 p-4 text-center"
                    >
                        <p class="font-money text-3xl font-extrabold">
                            #{{ item.queue_number }}
                        </p>
                        <p v-if="item.customer_name" class="mt-1 truncate text-xs text-latte/80">
                            {{ item.customer_name }}
                        </p>
                    </div>
                    <p
                        v-if="preparing.length === 0"
                        class="col-span-full py-12 text-center text-sm font-medium text-latte/70"
                    >
                        Belum ada pesanan yang sedang disiapkan.
                    </p>
                </div>
            </section>

            <section class="flex flex-col rounded-3xl border-2 border-emerald-500/60 bg-emerald-950/35 p-6">
                <div class="mb-5 flex items-center justify-between border-b border-emerald-400/20 pb-3">
                    <h2 class="text-xl font-extrabold tracking-wide text-emerald-300">
                        Siap Diambil
                    </h2>
                    <span class="font-money rounded-full bg-emerald-500/20 px-3 py-1 text-sm font-bold text-emerald-200">
                        {{ ready.length }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <div
                        v-for="item in ready"
                        :key="item.id"
                        class="rounded-2xl bg-emerald-600 p-4 text-center text-white shadow-md ring-2 ring-emerald-400/40"
                    >
                        <p class="font-money text-4xl font-extrabold">
                            #{{ item.queue_number }}
                        </p>
                        <p v-if="item.customer_name" class="mt-1 truncate text-xs font-bold">
                            {{ item.customer_name }}
                        </p>
                    </div>
                    <p
                        v-if="ready.length === 0"
                        class="col-span-full py-12 text-center text-sm font-medium text-emerald-200/80"
                    >
                        Belum ada pesanan siap diambil.
                    </p>
                </div>
            </section>
        </div>
    </div>
</template>
