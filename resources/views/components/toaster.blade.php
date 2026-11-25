<div
    x-data
    class="fixed right-4 bottom-4 z-[100] flex max-w-sm flex-col gap-2 print:hidden"
    aria-live="polite"
>
    <template x-for="toast in $store.toasts.items" :key="toast.id">
        <div
            x-transition
            class="flex items-start gap-3 rounded-xl border px-4 py-3 text-sm shadow-lg backdrop-blur-md"
            :class="{
                'border-emerald-500/40 bg-emerald-950/90 text-emerald-100': toast.type === 'success',
                'border-red-500/40 bg-red-950/90 text-red-100': toast.type === 'error',
                'border-border bg-card/95 text-card-foreground': toast.type !== 'success' && toast.type !== 'error',
            }"
        >
            <div class="mt-0.5 shrink-0">
                <template x-if="toast.type === 'success'">
                    <x-icon name="check-circle-2" class="size-4 text-emerald-400" />
                </template>
                <template x-if="toast.type === 'error'">
                    <x-icon name="alert-triangle" class="size-4 text-red-400" />
                </template>
                <template x-if="toast.type !== 'success' && toast.type !== 'error'">
                    <x-icon name="info" class="size-4 text-amber-400" />
                </template>
            </div>
            <div class="flex-1 leading-snug" x-text="toast.message"></div>
            <button
                type="button"
                @click="$store.toasts.remove(toast.id)"
                class="shrink-0 opacity-60 hover:opacity-100"
            >
                <x-icon name="x" class="size-3.5" />
            </button>
        </div>
    </template>
</div>
