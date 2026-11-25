<div class="grid grid-cols-3 gap-2">
    <template x-for="key in ['1', '2', '3', '4', '5', '6', '7', '8', '9', 'C', '0', 'BACK']" :key="key">
        <button
            type="button"
            @click="pressNumPad(key)"
            class="flex h-12 items-center justify-center rounded-xl border border-border bg-background font-money text-base font-bold transition hover:bg-muted active:scale-95"
        >
            <template x-if="key === 'BACK'">
                <x-icon name="delete" class="size-5" />
            </template>
            <template x-if="key !== 'BACK'">
                <span x-text="key"></span>
            </template>
        </button>
    </template>
</div>
