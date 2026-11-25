<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CheckCircle2, ChefHat, Clock, Volume2, VolumeX } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import { usePolling } from '@/composables/usePolling';
import { toast } from '@/composables/useToast';
import { jsonRequest } from '@/lib/http';
import { cn } from '@/lib/utils';

type TicketModifier = {
    group_name: string;
    option_name: string;
    is_default: boolean;
};

type TicketItem = {
    id: number;
    menu_item_name: string;
    quantity: number;
    notes: string | null;
    modifiers: TicketModifier[];
};

type KdsStatus = 'paid' | 'preparing' | 'ready' | 'completed';

type KdsTicket = {
    id: number;
    order_number: string;
    queue_number: string;
    channel: string;
    order_type: string;
    customer_name: string | null;
    status: KdsStatus;
    paid_at: string | null;
    preparing_at: string | null;
    ready_at: string | null;
    items: TicketItem[];
};

const props = defineProps<{
    tickets: KdsTicket[];
}>();

const tickets = ref<KdsTicket[]>(props.tickets);
const nowMs = ref(Date.now());
const muted = ref(false);
let knownPaidIds = new Set(
    props.tickets.filter((t) => t.status === 'paid').map((t) => t.id),
);
let clockTimer: number | null = null;

function playChime() {
    try {
        const AudioCtx =
            window.AudioContext ||
            (window as unknown as { webkitAudioContext?: typeof AudioContext })
                .webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        const now = ctx.currentTime;

        const playTone = (freq: number, start: number, duration: number) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(freq, start);
            gain.gain.setValueAtTime(0.15, start);
            gain.gain.exponentialRampToValueAtTime(0.001, start + duration);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(start);
            osc.stop(start + duration);
        };

        playTone(659.25, now, 0.16);
        playTone(880, now + 0.14, 0.28);
    } catch {
        // Ignore if audio context blocked
    }
}

function formatElapsed(
    paidAt: string | null,
    currentMs: number,
): { label: string; tier: 'green' | 'amber' | 'red' } {
    if (!paidAt) {
        return { label: '00:00', tier: 'green' };
    }
    const elapsedSec = Math.max(
        0,
        Math.floor((currentMs - new Date(paidAt).getTime()) / 1000),
    );
    const mins = Math.floor(elapsedSec / 60);
    const secs = elapsedSec % 60;
    const label = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;

    if (mins >= 10) return { label, tier: 'red' };
    if (mins >= 5) return { label, tier: 'amber' };
    return { label, tier: 'green' };
}

onMounted(() => {
    clockTimer = window.setInterval(() => {
        nowMs.value = Date.now();
    }, 1000);
});

onUnmounted(() => {
    if (clockTimer !== null) window.clearInterval(clockTimer);
});

async function refreshTickets() {
    try {
        const res = await jsonRequest<{ tickets: KdsTicket[] }>('/kds/tickets');
        if (!res.ok) return;

        const incomingPaid = res.data.tickets.filter((t) => t.status === 'paid');
        const hasNewPaid = incomingPaid.some((t) => !knownPaidIds.has(t.id));
        knownPaidIds = new Set(incomingPaid.map((t) => t.id));

        if (hasNewPaid && !muted.value) {
            playChime();
        }

        tickets.value = res.data.tickets;
    } catch {
        // Silent retry
    }
}

usePolling(refreshTickets, 3000, true);

async function sendTransition(ticketId: number, targetStatus: KdsStatus) {
    const res = await jsonRequest<{ ticket?: KdsTicket }>(
        `/kds/orders/${ticketId}/status`,
        {
            method: 'PATCH',
            body: { status: targetStatus },
        },
    );
    if (res.ok) {
        await refreshTickets();
    }
}

function handleAdvance(ticket: KdsTicket, nextStatus: KdsStatus) {
    const previousStatus = ticket.status;
    const previousSnapshot = { ...ticket };

    if (nextStatus === 'completed') {
        tickets.value = tickets.value.filter((t) => t.id !== ticket.id);
    } else {
        tickets.value = tickets.value.map((t) =>
            t.id === ticket.id ? { ...t, status: nextStatus } : t,
        );
    }

    void sendTransition(ticket.id, nextStatus);

    const nextLabel =
        nextStatus === 'preparing'
            ? 'Sedang Disiapkan'
            : nextStatus === 'ready'
              ? 'Siap Diambil'
              : 'Selesai';

    toast.success(`#${ticket.queue_number} → ${nextLabel}`, {
        duration: 5000,
        action: {
            label: 'Undo',
            onClick: () => {
                const exists = tickets.value.some((t) => t.id === ticket.id);
                if (!exists) {
                    tickets.value = [...tickets.value, previousSnapshot];
                } else {
                    tickets.value = tickets.value.map((t) =>
                        t.id === ticket.id
                            ? { ...t, status: previousStatus }
                            : t,
                    );
                }
                void sendTransition(ticket.id, previousStatus);
            },
        },
    });
}

const columns = computed(() => [
    {
        key: 'paid',
        title: 'Baru Masuk (Paid)',
        items: tickets.value.filter((t) => t.status === 'paid'),
        nextStatus: 'preparing' as KdsStatus,
        actionLabel: 'Proses',
        buttonClass: 'bg-crema text-white hover:bg-crema/90',
    },
    {
        key: 'preparing',
        title: 'Sedang Disiapkan',
        items: tickets.value.filter((t) => t.status === 'preparing'),
        nextStatus: 'ready' as KdsStatus,
        actionLabel: 'Tandai Siap',
        buttonClass: 'bg-emerald-600 text-white hover:bg-emerald-700',
    },
    {
        key: 'ready',
        title: 'Siap Diambil',
        items: tickets.value.filter((t) => t.status === 'ready'),
        nextStatus: 'completed' as KdsStatus,
        actionLabel: 'Selesai',
        buttonClass: 'bg-espresso text-oat hover:bg-espresso/90',
    },
]);
</script>

<template>
    <Head title="Kitchen Display System" />
    <div class="flex h-[calc(100vh-3rem)] flex-col gap-4 overflow-hidden">
        <header class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border bg-card px-5 py-3 shadow-xs">
            <div class="flex items-center gap-3">
                <ChefHat class="size-6 text-primary" />
                <div>
                    <h1 class="text-xl font-extrabold tracking-tight">
                        Kitchen Display System (KDS)
                    </h1>
                    <p class="text-xs text-muted-foreground">
                        Antrean FIFO terurut berdasarkan waktu lunas
                    </p>
                </div>
            </div>

            <Button
                variant="outline"
                @click="muted = !muted"
                class="min-h-[52px] gap-2 rounded-xl px-4 font-semibold"
            >
                <template v-if="muted">
                    <VolumeX class="size-5 text-muted-foreground" />
                    Suara Nonaktif
                </template>
                <template v-else>
                    <Volume2 class="size-5 text-primary" />
                    Notifikasi Suara Aktif
                </template>
            </Button>
        </header>

        <div class="grid min-h-0 flex-1 grid-cols-3 gap-4 overflow-hidden">
            <section
                v-for="col in columns"
                :key="col.key"
                class="flex min-h-0 flex-col overflow-hidden rounded-2xl border bg-muted/40"
            >
                <div class="flex items-center justify-between border-b bg-card px-4 py-3">
                    <h2 class="text-sm font-extrabold tracking-wide uppercase">
                        {{ col.title }}
                    </h2>
                    <Badge variant="secondary" class="font-money">
                        {{ col.items.length }}
                    </Badge>
                </div>

                <div class="flex-1 space-y-3 overflow-y-auto p-3">
                    <p
                        v-if="col.items.length === 0"
                        class="py-12 text-center text-xs text-muted-foreground"
                    >
                        Tidak ada pesanan pada tahap ini.
                    </p>

                    <div
                        v-for="ticket in col.items"
                        :key="ticket.id"
                        :class="
                            cn(
                                'flex flex-col gap-3 rounded-2xl border bg-card p-4 shadow-xs',
                                formatElapsed(ticket.paid_at, nowMs).tier === 'red' &&
                                    'border-2 border-red-600',
                            )
                        "
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-money text-2xl font-extrabold text-primary">
                                        #{{ ticket.queue_number }}
                                    </span>
                                    <Badge variant="outline">
                                        {{
                                            ticket.order_type === 'dine_in'
                                                ? 'Dine-In'
                                                : 'Takeaway'
                                        }}
                                    </Badge>
                                </div>
                                <p
                                    v-if="ticket.customer_name"
                                    class="mt-0.5 text-xs font-semibold text-muted-foreground"
                                >
                                    {{ ticket.customer_name }}
                                </p>
                            </div>

                            <span
                                :class="
                                    cn(
                                        'font-money inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold',
                                        formatElapsed(ticket.paid_at, nowMs).tier === 'green' &&
                                            'bg-emerald-100 text-emerald-900',
                                        formatElapsed(ticket.paid_at, nowMs).tier === 'amber' &&
                                            'bg-amber-100 text-amber-900',
                                        formatElapsed(ticket.paid_at, nowMs).tier === 'red' &&
                                            'bg-red-600 text-white',
                                    )
                                "
                            >
                                <Clock class="size-3.5" />
                                {{ formatElapsed(ticket.paid_at, nowMs).label }}
                            </span>
                        </div>

                        <div class="space-y-2.5 border-t pt-2.5">
                            <div
                                v-for="item in ticket.items"
                                :key="item.id"
                                class="space-y-1.5"
                            >
                                <p class="text-sm font-bold">
                                    <span class="font-money mr-1.5 text-primary">
                                        {{ item.quantity }}x
                                    </span>
                                    {{ item.menu_item_name }}
                                </p>

                                <div
                                    v-if="item.modifiers.filter((m) => !m.is_default).length > 0"
                                    class="flex flex-wrap gap-1.5 pl-5"
                                >
                                    <span
                                        v-for="(mod, idx) in item.modifiers.filter((m) => !m.is_default)"
                                        :key="idx"
                                        class="rounded-lg bg-espresso px-2 py-0.5 text-xs font-bold text-oat"
                                    >
                                        {{ mod.option_name }}
                                    </span>
                                </div>

                                <p
                                    v-if="item.modifiers.filter((m) => m.is_default).length > 0"
                                    class="pl-5 text-xs text-muted-foreground"
                                >
                                    {{
                                        item.modifiers
                                            .filter((m) => m.is_default)
                                            .map((m) => m.option_name)
                                            .join(' · ')
                                    }}
                                </p>

                                <div
                                    v-if="item.notes"
                                    class="ml-5 rounded-lg border border-amber-300 bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-950"
                                >
                                    Catatan: {{ item.notes }}
                                </div>
                            </div>
                        </div>

                        <Button
                            @click="handleAdvance(ticket, col.nextStatus)"
                            :class="
                                cn(
                                    'mt-1 min-h-[52px] w-full gap-2 rounded-xl text-base font-extrabold',
                                    col.buttonClass,
                                )
                            "
                        >
                            <CheckCircle2 class="size-5" />
                            {{ col.actionLabel }}
                        </Button>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
