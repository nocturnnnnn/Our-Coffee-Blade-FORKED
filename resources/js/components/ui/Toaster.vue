<script setup lang="ts">
import { CheckCircle2, Info, AlertTriangle, XCircle, X } from 'lucide-vue-next';
import { dismissToast, toastState } from '@/composables/useToast';
</script>

<template>
    <Teleport to="body">
        <div
            class="pointer-events-none fixed right-4 bottom-4 z-[100] flex max-w-sm flex-col gap-2"
            aria-live="polite"
        >
            <div
                v-for="item in toastState.items"
                :key="item.id"
                class="pointer-events-auto flex items-center gap-3 rounded-xl border border-border bg-card px-4 py-3 text-sm font-medium text-card-foreground shadow-lg transition-all"
            >
                <CheckCircle2
                    v-if="item.type === 'success'"
                    class="size-4 shrink-0 text-emerald-500"
                />
                <Info
                    v-else-if="item.type === 'info'"
                    class="size-4 shrink-0 text-sky-500"
                />
                <AlertTriangle
                    v-else-if="item.type === 'warning'"
                    class="size-4 shrink-0 text-amber-500"
                />
                <XCircle
                    v-else
                    class="size-4 shrink-0 text-destructive"
                />
                <span class="flex-1">{{ item.message }}</span>
                <button
                    v-if="item.actionLabel && item.onAction"
                    type="button"
                    @click="
                        item.onAction();
                        dismissToast(item.id);
                    "
                    class="rounded-md bg-primary px-2.5 py-1 text-xs font-semibold text-primary-foreground hover:bg-primary/90"
                >
                    {{ item.actionLabel }}
                </button>
                <button
                    type="button"
                    @click="dismissToast(item.id)"
                    class="rounded-md p-1 text-muted-foreground hover:bg-muted"
                >
                    <X class="size-3.5" />
                </button>
            </div>
        </div>
    </Teleport>
</template>
