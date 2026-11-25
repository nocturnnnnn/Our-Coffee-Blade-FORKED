<div
    x-show="exitModalOpen"
    x-cloak
    class="fixed inset-0 z-[70] flex items-center justify-center bg-black/80 p-4 backdrop-blur-xs"
>
    <div @click.outside="exitModalOpen = false" class="w-full max-w-xs rounded-3xl border border-border bg-card p-6 text-center shadow-2xl">
        <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-2xl bg-amber-500/15 text-amber-500">
            <x-icon name="lock" class="size-6" />
        </div>
        <h3 class="text-lg font-bold">Keluar Mode Kiosk</h3>
        <p class="mt-1 text-xs text-muted-foreground">Masukkan 6 digit PIN Admin untuk kembali ke Backoffice.</p>

        <div class="my-5 flex justify-center gap-2.5">
            <template x-for="idx in 6" :key="idx">
                <div
                    class="flex size-9 items-center justify-center rounded-lg border text-lg font-bold"
                    :class="exitPin.length >= idx ? 'border-amber-500 bg-amber-500/15 text-amber-400' : 'border-border bg-background text-muted-foreground'"
                    x-text="exitPin.length >= idx ? '•' : ''"
                ></div>
            </template>
        </div>

        <p x-show="exitError" x-text="exitError" class="mb-3 text-xs font-medium text-red-400"></p>

        <div class="grid grid-cols-3 gap-2">
            <template x-for="num in [1, 2, 3, 4, 5, 6, 7, 8, 9]" :key="num">
                <button
                    type="button"
                    @click="appendExitPin(num)"
                    class="h-12 rounded-xl border border-border bg-background font-money text-lg font-bold hover:bg-muted active:scale-95"
                    x-text="num"
                ></button>
            </template>
            <button
                type="button"
                @click="exitModalOpen = false"
                class="h-12 rounded-xl border border-border bg-background text-xs font-semibold text-muted-foreground hover:bg-muted"
            >
                Batal
            </button>
            <button
                type="button"
                @click="appendExitPin(0)"
                class="h-12 rounded-xl border border-border bg-background font-money text-lg font-bold hover:bg-muted active:scale-95"
            >
                0
            </button>
            <button
                type="button"
                @click="backspaceExitPin()"
                class="flex h-12 items-center justify-center rounded-xl border border-border bg-background hover:bg-muted active:scale-95"
            >
                <x-icon name="delete" class="size-5" />
            </button>
        </div>

        <button
            type="button"
            @click="submitExitPin()"
            :disabled="exitPin.length !== 6 || exitBusy"
            class="mt-4 flex h-11 w-full items-center justify-center rounded-xl bg-crema text-sm font-bold text-white hover:bg-crema/90 disabled:opacity-40"
        >
            <span x-show="!exitBusy">Verifikasi PIN</span>
            <span x-show="exitBusy">Memeriksa...</span>
        </button>
    </div>
</div>
