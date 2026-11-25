<div
    x-show="modifierItem"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center bg-black/70 p-0 backdrop-blur-xs sm:items-center sm:p-4"
>
    <div
        @click.outside="modifierItem = null"
        class="flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-t-3xl border border-border bg-card text-card-foreground shadow-2xl sm:rounded-3xl"
    >
        <div class="flex items-start justify-between gap-4 border-b border-border p-5">
            <div class="flex items-center gap-3.5">
                <img
                    :src="modifierItem?.image_url"
                    :alt="modifierItem?.name"
                    class="size-16 rounded-xl object-cover bg-muted"
                />
                <div>
                    <h3 class="text-lg font-bold" x-text="modifierItem?.name"></h3>
                    <p class="mt-0.5 text-xs text-muted-foreground line-clamp-2" x-text="modifierItem?.description"></p>
                    <p class="mt-1 font-money text-sm font-bold text-amber-500" x-text="window.ocFormatRupiah(modifierItem?.price)"></p>
                </div>
            </div>
            <button type="button" @click="modifierItem = null" class="rounded-full p-2 text-muted-foreground hover:bg-muted">
                <x-icon name="x" class="size-5" />
            </button>
        </div>

        <div class="flex-1 space-y-5 overflow-y-auto p-5">
            <template x-for="group in (modifierItem?.modifier_groups || [])" :key="group.id">
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-bold">
                            <span x-text="group.name"></span>
                            <span
                                x-show="group.is_required"
                                class="ml-1.5 rounded bg-amber-500/20 px-1.5 py-0.5 text-[10px] font-semibold text-amber-400"
                            >Wajib</span>
                        </div>
                        <span class="text-xs text-muted-foreground" x-text="group.selection_type === 'single' ? 'Pilih 1' : `Pilih maks ${group.max_select || 'bebas'}`"></span>
                    </div>

                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <template x-for="option in group.options" :key="option.id">
                            <button
                                type="button"
                                @click="toggleModifierOption(group, option)"
                                :class="isOptionSelected(group.id, option.id)
                                    ? 'border-primary bg-primary/15 text-foreground ring-1 ring-primary'
                                    : 'border-border bg-background/60 text-muted-foreground hover:border-foreground/30'"
                                class="flex min-h-12 items-center justify-between rounded-xl border px-3.5 py-2.5 text-left text-sm transition"
                            >
                                <span class="font-medium text-foreground" x-text="option.name"></span>
                                <span
                                    class="font-money text-xs font-semibold"
                                    :class="option.price_delta > 0 ? 'text-amber-500' : 'text-muted-foreground'"
                                    x-text="option.price_delta > 0 ? '+' + window.ocFormatRupiah(option.price_delta) : 'Gratis'"
                                ></span>
                            </button>
                        </template>
                    </div>
                </div>
            </template>

            <div class="space-y-2">
                <label class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Catatan (Opsional)</label>
                <input
                    type="text"
                    x-model="itemNotes"
                    maxlength="255"
                    placeholder="Contoh: Less ice, gula dipisah..."
                    class="flex h-11 w-full rounded-xl border border-input bg-background px-3.5 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                />
            </div>
        </div>

        <div class="flex items-center justify-between gap-4 border-t border-border bg-card p-5">
            <div class="flex items-center gap-2 rounded-xl border border-border bg-background p-1">
                <button
                    type="button"
                    @click="if (itemQty > 1) itemQty--"
                    class="flex size-10 items-center justify-center rounded-lg hover:bg-muted"
                >
                    <x-icon name="minus" class="size-4" />
                </button>
                <span class="w-8 text-center font-money text-base font-bold" x-text="itemQty"></span>
                <button
                    type="button"
                    @click="if (itemQty < 50) itemQty++"
                    class="flex size-10 items-center justify-center rounded-lg hover:bg-muted"
                >
                    <x-icon name="plus" class="size-4" />
                </button>
            </div>

            <button
                type="button"
                @click="addConfiguredItemToCart()"
                :disabled="!modifierSelectionValid"
                class="flex min-h-12 flex-1 items-center justify-center gap-2 rounded-xl bg-crema px-5 text-sm font-bold text-white shadow-sm transition hover:bg-crema/90 disabled:opacity-40"
            >
                <span>Tambah</span>
                <span class="font-money" x-text="window.ocFormatRupiah(modifierUnitPrice * itemQty)"></span>
            </button>
        </div>
    </div>
</div>
