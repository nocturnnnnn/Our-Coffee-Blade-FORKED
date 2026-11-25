<x-layouts.rail title="Modifier & Topping">
    <div x-data="adminModifiersApp()" class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Grup Modifier & Topping</h1>
                <p class="mt-1 text-sm text-muted-foreground">Atur pilihan ukuran, level gula, es, susu, serta pengurangan stok bahan otomatis per opsi.</p>
            </div>
            <button
                type="button"
                @click="openCreateGroup()"
                class="inline-flex h-10 items-center gap-2 rounded-xl bg-crema px-4 text-sm font-bold text-white hover:bg-crema/90"
            >
                <x-icon name="plus" class="size-4" />
                <span>Tambah Grup Modifier</span>
            </button>
        </div>

        <div class="space-y-4">
            @forelse ($modifierGroups as $grp)
                <section class="rounded-2xl border border-border bg-card p-5 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base font-bold">{{ $grp['name'] }}</h2>
                                <span class="rounded-lg bg-muted px-2 py-0.5 text-[11px] font-semibold">
                                    {{ $grp['selection_type'] === 'single' ? 'Pilih Satu' : 'Pilih Banyak' }}
                                </span>
                                @if ($grp['is_required'])
                                    <span class="rounded-lg bg-amber-500/15 px-2 py-0.5 text-[11px] font-semibold text-amber-400">Wajib</span>
                                @endif
                            </div>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                Dipakai di {{ $grp['menu_items_count'] }} menu · Min {{ $grp['min_select'] }} / Maks {{ $grp['max_select'] }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="openCreateOption({{ $grp['id'] }})"
                                class="inline-flex h-8 items-center gap-1 rounded-lg bg-crema/15 px-3 text-xs font-bold text-amber-400 hover:bg-crema/25"
                            >
                                <x-icon name="plus" class="size-3.5" />
                                <span>Tambah Opsi</span>
                            </button>
                            <button
                                type="button"
                                @click='openEditGroup(@json($grp))'
                                class="inline-flex h-8 items-center rounded-lg border border-border px-2.5 text-xs hover:bg-muted"
                            >
                                <x-icon name="edit-2" class="size-3.5" />
                            </button>
                            <form method="POST" action="{{ route('admin.modifiers.destroy', $grp['id']) }}" onsubmit="return confirm('Hapus grup {{ addslashes($grp['name']) }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex h-8 items-center rounded-lg border border-red-500/30 bg-red-500/10 px-2.5 text-xs text-red-400">
                                    <x-icon name="trash-2" class="size-3.5" />
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                        @forelse ($grp['options'] as $opt)
                            <div class="flex items-center justify-between gap-2 rounded-xl border border-border bg-background/60 p-3">
                                <div>
                                    <div class="text-sm font-semibold">
                                        {{ $opt['name'] }}
                                        @if ($opt['is_default'])
                                            <span class="ml-1 rounded bg-emerald-500/15 px-1.5 py-0.5 text-[10px] font-bold text-emerald-400">Default</span>
                                        @endif
                                    </div>
                                    <div class="font-money text-xs text-amber-500">
                                        {{ $opt['price_delta'] > 0 ? '+Rp '.number_format($opt['price_delta'], 0, ',', '.') : 'Gratis' }}
                                    </div>
                                    @if ($opt['ingredient_name'])
                                        <div class="text-[11px] text-muted-foreground">
                                            BOM: {{ $opt['ingredient_qty'] }} {{ $opt['ingredient_unit'] }} {{ $opt['ingredient_name'] }}
                                        </div>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1">
                                    <button
                                        type="button"
                                        @click='openEditOption({{ $grp['id'] }}, @json($opt))'
                                        class="rounded p-1.5 text-muted-foreground hover:bg-muted"
                                    >
                                        <x-icon name="edit-2" class="size-3.5" />
                                    </button>
                                    <form
                                        method="POST"
                                        action="{{ route('admin.modifiers.options.destroy', [$grp['id'], $opt['id']]) }}"
                                        onsubmit="return confirm('Hapus opsi {{ addslashes($opt['name']) }}?')"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded p-1.5 text-red-400 hover:bg-red-500/10">
                                            <x-icon name="trash-2" class="size-3.5" />
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <p class="col-span-full text-xs text-muted-foreground">Belum ada opsi pada grup ini.</p>
                        @endforelse
                    </div>
                </section>
            @empty
                <div class="rounded-2xl border border-border bg-card py-12 text-center text-sm text-muted-foreground">
                    Belum ada grup modifier.
                </div>
            @endforelse
        </div>

        {{-- Group Modal --}}
        <div x-show="groupModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-xs">
            <div @click.outside="groupModalOpen = false" class="w-full max-w-md rounded-2xl border border-border bg-card p-6 shadow-2xl">
                <h2 class="mb-4 text-lg font-bold" x-text="editingGroup ? 'Edit Grup Modifier' : 'Tambah Grup Modifier'"></h2>
                <form
                    method="POST"
                    :action="editingGroup ? `/admin/modifiers/${editingGroup.id}` : '{{ route('admin.modifiers.store') }}'"
                    class="space-y-4"
                >
                    @csrf
                    <template x-if="editingGroup">
                        <input type="hidden" name="_method" value="PUT" />
                    </template>

                    <div class="grid gap-1.5">
                        <label class="text-xs font-semibold">Nama Grup *</label>
                        <input type="text" name="name" x-model="groupForm.name" required class="h-10 rounded-lg border border-input bg-background px-3 text-sm" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-1.5">
                            <label class="text-xs font-semibold">Tipe Pilihan</label>
                            <select name="selection_type" x-model="groupForm.selection_type" class="h-10 rounded-lg border border-input bg-background px-3 text-sm">
                                <option value="single">Pilih Satu</option>
                                <option value="multiple">Pilih Banyak</option>
                            </select>
                        </div>
                        <div class="grid gap-1.5">
                            <label class="text-xs font-semibold">Urutan</label>
                            <input type="number" name="sort_order" min="0" x-model.number="groupForm.sort_order" class="h-10 rounded-lg border border-input bg-background px-3 font-money text-sm" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-1.5">
                            <label class="text-xs font-semibold">Min Pilih</label>
                            <input type="number" name="min_select" min="0" x-model.number="groupForm.min_select" class="h-10 rounded-lg border border-input bg-background px-3 font-money text-sm" />
                        </div>
                        <div class="grid gap-1.5">
                            <label class="text-xs font-semibold">Maks Pilih</label>
                            <input type="number" name="max_select" min="1" x-model.number="groupForm.max_select" class="h-10 rounded-lg border border-input bg-background px-3 font-money text-sm" />
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-xs font-medium cursor-pointer">
                        <input type="hidden" name="is_required" value="0" />
                        <input type="checkbox" name="is_required" value="1" x-model="groupForm.is_required" class="accent-primary" />
                        <span>Wajib dipilih pelanggan</span>
                    </label>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="groupModalOpen = false" class="h-10 rounded-xl border border-border px-4 text-sm">Batal</button>
                        <button type="submit" class="h-10 rounded-xl bg-crema px-5 text-sm font-bold text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Option Modal --}}
        <div x-show="optionModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-xs">
            <div @click.outside="optionModalOpen = false" class="w-full max-w-md rounded-2xl border border-border bg-card p-6 shadow-2xl">
                <h2 class="mb-4 text-lg font-bold" x-text="editingOption ? 'Edit Opsi Modifier' : 'Tambah Opsi Modifier'"></h2>
                <form
                    method="POST"
                    :action="editingOption
                        ? `/admin/modifiers/${optionGroupId}/options/${editingOption.id}`
                        : `/admin/modifiers/${optionGroupId}/options`"
                    class="space-y-4"
                >
                    @csrf
                    <template x-if="editingOption">
                        <input type="hidden" name="_method" value="PUT" />
                    </template>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-1.5">
                            <label class="text-xs font-semibold">Nama Opsi *</label>
                            <input type="text" name="name" x-model="optionForm.name" required class="h-10 rounded-lg border border-input bg-background px-3 text-sm" />
                        </div>
                        <div class="grid gap-1.5">
                            <label class="text-xs font-semibold">Tambahan Harga (Rp) *</label>
                            <input type="number" name="price_delta" min="0" x-model.number="optionForm.price_delta" required class="h-10 rounded-lg border border-input bg-background px-3 font-money text-sm" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-1.5">
                            <label class="text-xs font-semibold">Potong Bahan (Opsional)</label>
                            <select name="ingredient_id" x-model="optionForm.ingredient_id" class="h-10 rounded-lg border border-input bg-background px-3 text-xs">
                                <option value="">Tidak memotong stok</option>
                                @foreach ($ingredients as $ing)
                                    <option value="{{ $ing['id'] }}">{{ $ing['name'] }} ({{ $ing['unit'] }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid gap-1.5">
                            <label class="text-xs font-semibold">Takaran Bahan</label>
                            <input
                                type="number"
                                step="0.001"
                                min="0.001"
                                name="ingredient_qty"
                                x-model="optionForm.ingredient_qty"
                                :required="Boolean(optionForm.ingredient_id)"
                                :disabled="!optionForm.ingredient_id"
                                class="h-10 rounded-lg border border-input bg-background px-3 font-money text-xs disabled:opacity-50"
                            />
                        </div>
                    </div>

                    <div class="grid gap-1.5">
                        <label class="text-xs font-semibold">Urutan</label>
                        <input type="number" name="sort_order" min="0" x-model.number="optionForm.sort_order" class="h-10 rounded-lg border border-input bg-background px-3 font-money text-sm" />
                    </div>

                    <div class="flex gap-5">
                        <label class="flex items-center gap-2 text-xs cursor-pointer">
                            <input type="hidden" name="is_default" value="0" />
                            <input type="checkbox" name="is_default" value="1" x-model="optionForm.is_default" class="accent-primary" />
                            <span>Opsi Default</span>
                        </label>
                        <label class="flex items-center gap-2 text-xs cursor-pointer">
                            <input type="hidden" name="is_available" value="0" />
                            <input type="checkbox" name="is_available" value="1" x-model="optionForm.is_available" class="accent-primary" />
                            <span>Tersedia</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="optionModalOpen = false" class="h-10 rounded-xl border border-border px-4 text-sm">Batal</button>
                        <button type="submit" class="h-10 rounded-xl bg-crema px-5 text-sm font-bold text-white">Simpan Opsi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.rail>
