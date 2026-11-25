<x-layouts.rail title="Menu & BOM">
    <script type="application/json" id="admin-menus-data">
        @json(['ingredients' => $ingredients])
    </script>

    <div x-data="adminMenusApp('admin-menus-data')" class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Menu & Resep BOM</h1>
                <p class="mt-1 text-sm text-muted-foreground">Kelola katalog menu, harga jual, modifier, dan resep bahan baku (COGS).</p>
            </div>
            <button
                type="button"
                @click="openCreate('{{ $categories[0]['id'] ?? '' }}')"
                class="inline-flex h-10 items-center gap-2 rounded-xl bg-crema px-4 text-sm font-bold text-white hover:bg-crema/90"
            >
                <x-icon name="plus" class="size-4" />
                <span>Tambah Menu</span>
            </button>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('admin.menus.index') }}" class="flex flex-wrap items-center gap-3 rounded-2xl border border-border bg-card p-4">
            <div class="relative flex-1 min-w-[200px]">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                <input
                    type="search"
                    name="search"
                    value="{{ $filters['search'] }}"
                    placeholder="Cari nama menu..."
                    class="flex h-9 w-full rounded-lg border border-input bg-background pl-9 pr-3 text-sm"
                />
            </div>
            <select name="category_id" class="h-9 rounded-lg border border-input bg-background px-3 text-sm">
                <option value="">Semua Kategori</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat['id'] }}" @selected($filters['category_id'] === $cat['id'])>{{ $cat['name'] }}</option>
                @endforeach
            </select>
            <button type="submit" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-primary px-4 text-xs font-bold text-primary-foreground">
                <x-icon name="filter" class="size-3.5" />
                <span>Filter</span>
            </button>
        </form>

        {{-- Table --}}
        <div class="rounded-2xl border border-border bg-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-border bg-muted/50 text-xs text-muted-foreground uppercase">
                        <tr>
                            <th class="px-4 py-3">Menu</th>
                            <th class="px-4 py-3">Kategori</th>
                            <th class="px-4 py-3 text-right">Harga</th>
                            <th class="px-4 py-3 text-right">HPP / Margin</th>
                            <th class="px-4 py-3 text-center">Ketersediaan</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($menus as $menu)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $menu['image_url'] }}" alt="{{ $menu['name'] }}" class="size-11 rounded-xl object-cover bg-muted shrink-0" />
                                        <div>
                                            <div class="font-semibold">{{ $menu['name'] }}</div>
                                            <div class="text-xs text-muted-foreground">
                                                {{ count($menu['recipe_items']) }} bahan BOM · {{ count($menu['modifier_group_ids']) }} modifier
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs">{{ $menu['category_name'] }}</td>
                                <td class="px-4 py-3 text-right font-money font-bold">Rp {{ number_format($menu['price'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="font-money text-xs">Rp {{ number_format($menu['cogs'], 0, ',', '.') }}</div>
                                    <div class="font-money text-xs font-bold text-emerald-400">{{ $menu['gross_margin_percent'] }}%</div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <form method="POST" action="{{ route('admin.menus.toggle-availability', $menu['id']) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button
                                            type="submit"
                                            @class([
                                                'rounded-full px-2.5 py-0.5 text-xs font-semibold transition',
                                                'bg-emerald-500/15 text-emerald-400 hover:bg-emerald-500/25' => $menu['effective_available'],
                                                'bg-red-500/15 text-red-400 hover:bg-red-500/25' => ! $menu['effective_available'],
                                            ])
                                        >
                                            {{ $menu['effective_available'] ? 'Tersedia' : ($menu['unavailable_reason'] ?: 'Habis') }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            @click='openRecipe(@json($menu))'
                                            class="inline-flex h-8 items-center gap-1 rounded-lg border border-amber-500/30 bg-amber-500/10 px-2.5 text-xs font-semibold text-amber-400 hover:bg-amber-500/20"
                                        >
                                            <x-icon name="flask-conical" class="size-3.5" />
                                            <span>Resep</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click='openEdit(@json($menu))'
                                            class="inline-flex h-8 items-center rounded-lg border border-border px-2.5 text-xs font-medium hover:bg-muted"
                                        >
                                            <x-icon name="edit-2" class="size-3.5" />
                                        </button>
                                        <form
                                            method="POST"
                                            action="{{ route('admin.menus.destroy', $menu['id']) }}"
                                            onsubmit="return confirm('Hapus menu {{ addslashes($menu['name']) }}?')"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex h-8 items-center rounded-lg border border-red-500/30 bg-red-500/10 px-2.5 text-xs text-red-400 hover:bg-red-500/20">
                                                <x-icon name="trash-2" class="size-3.5" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-muted-foreground">Belum ada data menu.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Create / Edit Menu Modal --}}
        <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-xs">
            <div @click.outside="modalOpen = false" class="flex max-h-[92vh] w-full max-w-xl flex-col rounded-2xl border border-border bg-card shadow-2xl">
                <div class="flex items-center justify-between border-b border-border p-5">
                    <h2 class="text-lg font-bold" x-text="editing ? 'Edit Menu' : 'Tambah Menu'"></h2>
                    <button type="button" @click="modalOpen = false" class="rounded-lg p-1.5 hover:bg-muted">
                        <x-icon name="x" class="size-4" />
                    </button>
                </div>

                <form
                    method="POST"
                    enctype="multipart/form-data"
                    :action="editing ? `/admin/menus/${editing.id}` : '{{ route('admin.menus.store') }}'"
                    class="flex-1 space-y-4 overflow-y-auto p-5"
                >
                    @csrf
                    <template x-if="editing">
                        <input type="hidden" name="_method" value="PUT" />
                    </template>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <label class="text-xs font-semibold">Nama Menu *</label>
                            <input type="text" name="name" x-model="form.name" required class="h-10 rounded-lg border border-input bg-background px-3 text-sm" />
                        </div>
                        <div class="grid gap-1.5">
                            <label class="text-xs font-semibold">Kategori *</label>
                            <select name="category_id" x-model="form.category_id" required class="h-10 rounded-lg border border-input bg-background px-3 text-sm">
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat['id'] }}">{{ $cat['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="grid gap-1.5">
                            <label class="text-xs font-semibold">Harga Jual (Rp) *</label>
                            <input type="number" name="price" min="0" x-model.number="form.price" required class="h-10 rounded-lg border border-input bg-background px-3 font-money text-sm" />
                        </div>
                        <div class="grid gap-1.5">
                            <label class="text-xs font-semibold">Badge</label>
                            <select name="badge" x-model="form.badge" class="h-10 rounded-lg border border-input bg-background px-3 text-sm">
                                <option value="">Tanpa Badge</option>
                                <option value="best_seller">Best Seller</option>
                                <option value="new">New</option>
                            </select>
                        </div>
                        <div class="grid gap-1.5">
                            <label class="text-xs font-semibold">Urutan</label>
                            <input type="number" name="sort_order" min="0" x-model.number="form.sort_order" class="h-10 rounded-lg border border-input bg-background px-3 font-money text-sm" />
                        </div>
                    </div>

                    <div class="grid gap-1.5">
                        <label class="text-xs font-semibold">Deskripsi</label>
                        <textarea name="description" rows="2" x-model="form.description" class="rounded-lg border border-input bg-background p-3 text-sm"></textarea>
                    </div>

                    <div class="grid gap-1.5">
                        <label class="text-xs font-semibold">Foto Menu (JPG/PNG/WEBP maks 2MB)</label>
                        <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="text-xs" />
                        <template x-if="editing">
                            <label class="mt-1 flex items-center gap-2 text-xs text-muted-foreground">
                                <input type="hidden" name="remove_image" value="0" />
                                <input type="checkbox" name="remove_image" value="1" x-model="form.remove_image" class="accent-primary" />
                                <span>Hapus gambar saat ini</span>
                            </label>
                        </template>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-semibold">Grup Modifier</label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach ($modifierGroups as $grp)
                                <label class="flex items-center gap-2 rounded-lg border border-border bg-background/60 px-3 py-2 text-xs cursor-pointer">
                                    <input
                                        type="checkbox"
                                        name="modifier_group_ids[]"
                                        value="{{ $grp['id'] }}"
                                        :checked="form.modifier_group_ids.includes({{ $grp['id'] }})"
                                        @change="toggleModifierGroup({{ $grp['id'] }})"
                                        class="accent-primary"
                                    />
                                    <span>{{ $grp['name'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-5 pt-1">
                        <label class="flex items-center gap-2 text-xs font-medium cursor-pointer">
                            <input type="hidden" name="is_available" value="0" />
                            <input type="checkbox" name="is_available" value="1" x-model="form.is_available" class="accent-primary" />
                            <span>Tersedia (Stok Aman)</span>
                        </label>
                        <label class="flex items-center gap-2 text-xs font-medium cursor-pointer">
                            <input type="hidden" name="is_active" value="0" />
                            <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="accent-primary" />
                            <span>Menu Aktif</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-border pt-4">
                        <button type="button" @click="modalOpen = false" class="h-10 rounded-xl border border-border px-4 text-sm font-medium">Batal</button>
                        <button type="submit" class="h-10 rounded-xl bg-crema px-5 text-sm font-bold text-white hover:bg-crema/90">Simpan Menu</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- BOM Recipe Modal --}}
        <div x-show="recipeModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-xs">
            <div @click.outside="recipeModalOpen = false" class="flex max-h-[90vh] w-full max-w-xl flex-col rounded-2xl border border-border bg-card shadow-2xl">
                <div class="flex items-center justify-between border-b border-border p-5">
                    <div>
                        <h2 class="text-lg font-bold">Resep BOM: <span x-text="recipeMenu?.name"></span></h2>
                        <p class="text-xs text-muted-foreground">
                            Estimasi HPP: <span class="font-money font-bold text-amber-400" x-text="window.ocFormatRupiah(estimatedRecipeCogs)"></span>
                        </p>
                    </div>
                    <button type="button" @click="recipeModalOpen = false" class="rounded-lg p-1.5 hover:bg-muted">
                        <x-icon name="x" class="size-4" />
                    </button>
                </div>

                <form
                    method="POST"
                    :action="recipeMenu ? `/admin/menus/${recipeMenu.id}/recipe` : '#'"
                    class="flex-1 space-y-4 overflow-y-auto p-5"
                >
                    @csrf
                    @method('PUT')

                    <div class="space-y-2.5">
                        <template x-for="(row, idx) in recipeRows" :key="idx">
                            <div class="flex items-center gap-2">
                                <select
                                    :name="`ingredients[${idx}][ingredient_id]`"
                                    x-model="row.ingredient_id"
                                    required
                                    class="h-10 flex-1 rounded-lg border border-input bg-background px-3 text-xs"
                                >
                                    <template x-for="ing in ingredients" :key="ing.id">
                                        <option :value="String(ing.id)" x-text="`${ing.name} (${ing.unit})`"></option>
                                    </template>
                                </select>
                                <input
                                    type="number"
                                    step="0.001"
                                    min="0.001"
                                    :name="`ingredients[${idx}][quantity]`"
                                    x-model.number="row.quantity"
                                    required
                                    placeholder="Takaran"
                                    class="h-10 w-28 rounded-lg border border-input bg-background px-3 font-money text-xs"
                                />
                                <button type="button" @click="removeRecipeRow(idx)" class="flex size-10 items-center justify-center rounded-lg border border-red-500/30 bg-red-500/10 text-red-400">
                                    <x-icon name="trash-2" class="size-4" />
                                </button>
                            </div>
                        </template>
                    </div>

                    <button type="button" @click="addRecipeRow()" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-border px-3 text-xs font-semibold hover:bg-muted">
                        <x-icon name="plus" class="size-3.5" />
                        <span>Tambah Bahan</span>
                    </button>

                    <div class="flex justify-end gap-2 border-t border-border pt-4">
                        <button type="button" @click="recipeModalOpen = false" class="h-10 rounded-xl border border-border px-4 text-sm font-medium">Batal</button>
                        <button type="submit" class="h-10 rounded-xl bg-crema px-5 text-sm font-bold text-white hover:bg-crema/90">Simpan Resep BOM</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.rail>
