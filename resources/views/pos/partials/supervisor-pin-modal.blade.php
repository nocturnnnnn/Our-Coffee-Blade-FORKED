<div
    x-show="voidModalOpen"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-xs"
>
    <div @click.outside="voidModalOpen = false" class="w-full max-w-md rounded-2xl border border-border bg-card p-6 shadow-2xl">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h3 class="text-lg font-bold text-red-400">Void Transaksi</h3>
                <p class="mt-0.5 text-xs text-muted-foreground">
                    Pesanan <span class="font-money font-semibold text-foreground" x-text="voidTargetOrder?.order_number"></span> — stok bahan baku akan dikembalikan otomatis.
                </p>
            </div>
            <button type="button" @click="voidModalOpen = false" class="rounded-lg p-1.5 hover:bg-muted">
                <x-icon name="x" class="size-4" />
            </button>
        </div>

        <p x-show="voidError" x-text="voidError" class="mt-3 rounded-lg bg-red-500/15 p-2.5 text-xs text-red-400"></p>

        <div class="mt-4 space-y-4">
            <div class="grid gap-1.5">
                <label class="text-xs font-semibold">Alasan Void <span class="text-red-400">*</span></label>
                <input
                    type="text"
                    x-model="voidReason"
                    placeholder="Contoh: Salah input menu / pelanggan membatalkan"
                    class="flex h-10 w-full rounded-lg border border-input bg-background px-3 text-sm"
                />
            </div>

            <div class="grid gap-1.5">
                <label class="text-xs font-semibold">PIN Supervisor / Admin (6 Digit) <span class="text-red-400">*</span></label>
                <input
                    type="password"
                    x-model="voidPin"
                    maxlength="6"
                    inputmode="numeric"
                    placeholder="••••••"
                    class="flex h-10 w-full rounded-lg border border-input bg-background px-3 font-money text-sm tracking-widest"
                />
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" @click="voidModalOpen = false" class="h-10 rounded-xl border border-border px-4 text-sm font-medium hover:bg-muted">
                Batal
            </button>
            <button
                type="button"
                @click="submitVoid()"
                :disabled="!voidReason.trim() || voidPin.length !== 6 || voidBusy"
                class="h-10 rounded-xl bg-red-600 px-5 text-sm font-bold text-white hover:bg-red-500 disabled:opacity-40"
            >
                <span x-show="!voidBusy">Konfirmasi Void</span>
                <span x-show="voidBusy">Memproses...</span>
            </button>
        </div>
    </div>
</div>
