<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Filter } from 'lucide-vue-next';
import { ref } from 'vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import { formatDateWib, formatTimeWib } from '@/lib/format';

type AuditLogRow = {
    id: number;
    action: string;
    actor_name: string | null;
    authorizer_name: string | null;
    auditable_type: string | null;
    auditable_id: number | null;
    reason: string | null;
    metadata: Record<string, unknown> | null;
    ip_address: string | null;
    created_at: string | null;
};

const props = defineProps<{
    logs: AuditLogRow[];
    actions: string[];
    staffList: Array<{ id: number; name: string }>;
    filters: {
        action: string;
        actor_id: number | null;
        start_date: string;
        end_date: string;
    };
}>();

const action = ref(props.filters.action);
const actorId = ref(props.filters.actor_id ? String(props.filters.actor_id) : '');
const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);

function applyFilter() {
    router.get(
        '/admin/audit-logs',
        {
            action: action.value || undefined,
            actor_id: actorId.value || undefined,
            start_date: startDate.value || undefined,
            end_date: endDate.value || undefined,
        },
        { preserveState: true },
    );
}
</script>

<template>
    <Head title="Audit Log" />
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">
                Audit Log Aktivitas Kritis
            </h1>
            <p class="text-sm text-muted-foreground">
                Jejak baca-saja untuk void pesanan, diskon manual, stock opname, waste, selisih shift, dan perubahan otorisasi.
            </p>
        </div>

        <form
            @submit.prevent="applyFilter"
            class="flex flex-wrap items-end gap-3 rounded-2xl border bg-card p-4 shadow-xs"
        >
            <div class="space-y-1">
                <Label for="aud-action">Aksi</Label>
                <select
                    id="aud-action"
                    v-model="action"
                    class="flex h-10 rounded-md border border-input bg-background px-3 text-sm"
                >
                    <option value="">Semua Aksi</option>
                    <option v-for="act in actions" :key="act" :value="act">
                        {{ act }}
                    </option>
                </select>
            </div>
            <div class="space-y-1">
                <Label for="aud-actor">Staf / Otorisator</Label>
                <select
                    id="aud-actor"
                    v-model="actorId"
                    class="flex h-10 rounded-md border border-input bg-background px-3 text-sm"
                >
                    <option value="">Semua Staf</option>
                    <option v-for="s in staffList" :key="s.id" :value="s.id">
                        {{ s.name }}
                    </option>
                </select>
            </div>
            <div class="space-y-1">
                <Label for="aud-start">Dari</Label>
                <Input id="aud-start" type="date" v-model="startDate" />
            </div>
            <div class="space-y-1">
                <Label for="aud-end">Sampai</Label>
                <Input id="aud-end" type="date" v-model="endDate" />
            </div>
            <Button type="submit" variant="outline" class="gap-2">
                <Filter class="size-4" />
                Filter
            </Button>
        </form>

        <div class="overflow-x-auto rounded-2xl border bg-card shadow-xs">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-muted/60 text-xs text-muted-foreground uppercase">
                    <tr>
                        <th class="px-4 py-3">Waktu (WIB)</th>
                        <th class="px-4 py-3">Aksi</th>
                        <th class="px-4 py-3">Pelaku</th>
                        <th class="px-4 py-3">Otorisasi PIN</th>
                        <th class="px-4 py-3">Alasan & Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="log in logs" :key="log.id">
                        <td class="font-money px-4 py-3 text-xs text-muted-foreground">
                            {{
                                log.created_at
                                    ? `${formatDateWib(log.created_at)} ${formatTimeWib(log.created_at)}`
                                    : '-'
                            }}
                        </td>
                        <td class="px-4 py-3">
                            <Badge variant="outline">{{ log.action }}</Badge>
                        </td>
                        <td class="px-4 py-3">{{ log.actor_name ?? 'Sistem' }}</td>
                        <td class="px-4 py-3">{{ log.authorizer_name ?? '-' }}</td>
                        <td class="px-4 py-3 text-xs">
                            <p class="font-medium">{{ log.reason ?? '-' }}</p>
                            <p
                                v-if="log.metadata"
                                class="mt-1 font-mono text-[11px] text-muted-foreground"
                            >
                                {{ JSON.stringify(log.metadata) }}
                            </p>
                        </td>
                    </tr>
                    <tr v-if="logs.length === 0">
                        <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                            Belum ada catatan audit log yang cocok.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
