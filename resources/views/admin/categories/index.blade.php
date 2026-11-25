<x-layouts.rail title="Kategori Menu">
    <div x-data="adminCategoriesApp()" class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Kategori Menu</h1>
                <p class="mt-1 text-sm text-muted-foreground">Atur kelompok menu yang tampil di Kiosk dan POS Kasir.</p>
            </div>
            <button
                type="button"
                @click="openCreate()"
                class="inline-flex h-10 items-center gap-2 rounded-xl bg-crema px-4 text-sm font-bold text-white hover:bg-crema/90"
            >
                <x-icon name="folder-plus" class="size-4" />
                <span>Tambah Kategori</span>
            </button>
        </div>

        <div class="rounded-2xl border border-border bg-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-border bg-muted/50 text-xs text-muted-foreground uppercase">
                        <tr>
                            <th class="px-4 py-3">Kategori</th>
                            <th class="px-4 py-3">Icon</th>
                            <th class="px-4 py-3 text-center">Urutan</th>
                            <th class="px-4 py-3 text-center">Jumlah Menu</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($categories as $cat)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold">{{ $cat['name'] }}</div>
                                    <div class="font-mono text-xs text-muted-foreground">{{ $cat['slug'] }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">{{ $cat['icon'] }}</td>
                                <td class="px-4 py-3 text-center font-money">{{ $cat['sort_order'] }}</td>
                                <td class="px-4 py-3 text-center font-money">{{ $cat['menu_items_count'] }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span @class([
                                        'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                        'bg-emerald-500/15 text-emerald-400' => $cat['is_active'],
                                        'bg-muted text-muted-foreground' => ! $cat['is_active'],
                                    ])>
                                        {{ $cat['is_active'] ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <button
                                            type="button"
                                            @click='openEdit(@json($cat))'
                                            class="inline-flex h-8 items-center gap-1 rounded-lg border border-border px-2.5 text-xs font-medium hover:bg-muted"
                                        >
                                            <x-icon name="edit-2" class="size-3.5" />
                                            <span>Edit</span>
                                        </button>
                                        <form
                                            method="POST"
                                            action="{{ route('admin.categories.destroy', $cat['id']) }}"
                                            onsubmit="return confirm('Hapus kategori {{ addslashes($cat['name']) }}?')"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex h-8 items-center rounded-lg border border-red-500/30 bg-red-500/10 px-2.5 text-xs font-medium text-red-400 hover:bg-red-500/20">
                                                <x-icon name="trash-2" class="size-3.5" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-muted-foreground">Belum ada kategori menu.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Create / Edit Modal --}}
        <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-xs">
            <div @click.outside="modalOpen = false" class="w-full max-w-md rounded-2xl border border-border bg-card p-6 shadow-2xl">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold" x-text="editing ? 'Edit Kategori' : 'Tambah Kategori'"></h2>
                    <button type="button" @click="modalOpen = false" class="rounded-lg p-1.5 hover:bg-muted">
                        <x-icon name="x" class="size-4" />
                    </button>
                </div>

                <form
                    method="POST"
                    :action="editing ? `/admin/categories/${editing.id}` : '{{ route('admin.categories.store') }}'"
                    class="space-y-4"
                >
                    @csrf
                    <template x-if="editing">
                        <input type="hidden" name="_method" value="PUT" />
                    </template>

                    <div class="grid gap-1.5">
                        <label class="text-xs font-semibold">Nama Kategori <span class="text-red-400">*</span></label>
                        <input type="text" name="name" x-model="form.name" required class="h-10 rounded-lg border border-input bg-background px-3 text-sm" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-1.5">
                            <label class="text-xs font-semibold">Icon</label>
                            <select name="icon" x-model="form.icon" class="h-10 rounded-lg border border-input bg-background px-3 text-sm">
                                <option value="coffee">Coffee</option>
                                <option value="cup-soda">Cold Drink</option>
                                <option value="croissant">Pastry</option>
                                <option value="sparkles">Signature</option>
                                <option value="utensils">Food</option>
                            </select>
                        </div>
                        <div class="grid gap-1.5">
                            <label class="text-xs font-semibold">Urutan Tampil</label>
                            <input type="number" name="sort_order" min="0" x-model.number="form.sort_order" class="h-10 rounded-lg border border-input bg-background px-3 font-money text-sm" />
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                        <input type="hidden" name="is_active" value="0" />
                        <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="size-4 accent-primary" />
                        <span>Aktif (Tampilkan di Kiosk & POS)</span>
                    </label>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="modalOpen = false" class="h-10 rounded-xl border border-border px-4 text-sm font-medium">Batal</button>
                        <button type="submit" class="h-10 rounded-xl bg-crema px-5 text-sm font-bold text-white hover:bg-crema/90">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.rail>
