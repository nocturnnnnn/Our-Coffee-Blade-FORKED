<x-layouts.rail title="Inventaris Bahan Baku">
    <div x-data="adminInventoryApp()" class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Inventaris & Mutasi Stok</h1>
                <p class="mt-1 text-sm text-muted-foreground">Pantau stok bahan baku, Stock In distributor, Waste, dan Stock Opname.</p>
            </div>
            <button
                type="button"
                @click="openCreateIngredient()"
                class="inline-flex h-10 items-center gap-2 rounded-xl bg-crema px-4 text-sm font-bold text-white hover:bg-crema/90"
            >
                <x-icon name="plus" class="size-4" />
                <span>Tambah Bahan Baku</span>
            </button>
        </div>

        {{-- Ingredients Table --}}
        <div class="rounded-2xl border border-border bg-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-border bg-muted/50 text-xs text-muted-foreground uppercase">
                        <tr>
                            <th class="px-4 py-3">Bahan Baku</th>
                            <th class="px-4 py-3">Stok Saat Ini</th>
                            <th class="px-4 py-3 text-right">Harga Satuan (WAC)</th>
                            <th class="px-4 py-3">Distributor Default</th>
                            <th class="px-4 py-3 text-right">Aksi Stok</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($ingredients as $ing)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold flex items-center gap-2">
                                        <span>{{ $ing['name'] }}</span>
                                        @if ($ing['is_low_stock'])
                                            <span class="rounded bg-red-500/15 px-1.5 py-0.5 text-[10px] font-bold text-red-400">MENIPIS</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-muted-foreground">SKU: {{ $ing['sku'] ?: '-' }} · Min: {{ $ing['min_stock'] }} {{ $ing['unit'] }}</div>
                                </td>
                                <td class="px-4 py-3 w-48">
                                    <div class="font-money font-bold">{{ $ing['current_stock'] }} {{ $ing['unit'] }}</div>
                                    <div class="mt-1.5">
                                        <x-stock-level-bar :current="$ing['current_stock']" :min="$ing['min_stock']" />
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right font-money">Rp {{ number_format($ing['cost_per_unit'], 0, ',', '.') }} / {{ $ing['unit'] }}</td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">{{ $ing['default_distributor_name'] ?: '-' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex flex-wrap justify-end gap-1.5">
                                        <button type="button" @click='openRestock(@json($ing))' class="inline-flex h-8 items-center gap-1 rounded-lg bg-emerald-600/15 px-2.5 text-xs font-semibold text-emerald-400 hover:bg-emerald-600/25">
                                            <x-icon name="arrow-down-to-line" class="size-3.5" />
                                            <span>Restock</span>
                                        </button>
                                        <button type="button" @click='openWaste(@json($ing))' class="inline-flex h-8 items-center gap-1 rounded-lg bg-red-500/15 px-2.5 text-xs font-semibold text-red-400 hover:bg-red-500/25">
                                            <x-icon name="ban" class="size-3.5" />
                                            <span>Waste</span>
                                        </button>
                                        <button type="button" @click='openOpname(@json($ing))' class="inline-flex h-8 items-center gap-1 rounded-lg bg-amber-500/15 px-2.5 text-xs font-semibold text-amber-400 hover:bg-amber-500/25">
                                            <x-icon name="clipboard-check" class="size-3.5" />
                                            <span>Opname</span>
                                        </button>
                                        <button type="button" @click='openEditIngredient(@json($ing))' class="inline-flex h-8 items-center rounded-lg border border-border px-2.5 text-xs hover:bg-muted">
                                            <x-icon name="edit-2" class="size-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-muted-foreground">Belum ada bahan baku.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Stock Movement Log --}}
        <div class="rounded-2xl border border-border bg-card overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border px-5 py-4">
                <h2 class="text-base font-bold">Riwayat Mutasi Stok Terbaru</h2>
                <form method="GET" action="{{ route('admin.inventory.index') }}" class="flex flex-wrap items-center gap-2">
                    <select name="ingredient_id" class="h-8 rounded-lg border border-input bg-background px-2.5 text-xs">
                        <option value="">Semua Bahan</option>
                        @foreach ($ingredients as $ing)
                            <option value="{{ $ing['id'] }}" @selected($filters['ingredient_id'] === $ing['id'])>{{ $ing['name'] }}</option>
                        @endforeach
                    </select>
                    <select name="type" class="h-8 rounded-lg border border-input bg-background px-2.5 text-xs">
                        <option value="">Semua Tipe</option>
                        <option value="restock" @selected($filters['type'] === 'restock')>Stock In</option>
                        <option value="sale" @selected($filters['type'] === 'sale')>Penjualan</option>
                        <option value="waste" @selected($filters['type'] === 'waste')>Waste / Rusak</option>
                        <option value="opname" @selected($filters['type'] === 'opname')>Stock Opname</option>
                    </select>
                    <button type="submit" class="h-8 rounded-lg bg-primary px-3 text-xs font-bold text-primary-foreground">Filter</button>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-border bg-muted/50 text-muted-foreground uppercase">
                        <tr>
                            <th class="px-4 py-2.5">Waktu</th>
                            <th class="px-4 py-2.5">Bahan</th>
                            <th class="px-4 py-2.5">Tipe</th>
                            <th class="px-4 py-2.5 text-right">Perubahan</th>
                            <th class="px-4 py-2.5 text-right">Saldo Akhir</th>
                            <th class="px-4 py-2.5">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($movements as $mov)
                            <tr>
                                <td class="px-4 py-2.5 text-muted-foreground">
                                    {{ $mov['created_at'] ? \Carbon\Carbon::parse($mov['created_at'])->setTimezone('Asia/Jakarta')->format('d/m H:i') : '-' }}
                                </td>
                                <td class="px-4 py-2.5 font-semibold">{{ $mov['ingredient_name'] }}</td>
                                <td class="px-4 py-2.5">{{ $mov['type_label'] }}</td>
                                <td @class([
                                    'px-4 py-2.5 text-right font-money font-bold',
                                    'text-emerald-400' => $mov['quantity_change'] >= 0,
                                    'text-red-400' => $mov['quantity_change'] < 0,
                                ])>
                                    {{ $mov['quantity_change'] > 0 ? '+' : '' }}{{ $mov['quantity_change'] }} {{ $mov['unit'] }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-money">{{ $mov['stock_after'] }} {{ $mov['unit'] }}</td>
                                <td class="px-4 py-2.5 text-muted-foreground">{{ $mov['notes'] ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">Belum ada riwayat mutasi stok.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Ingredient Create/Edit Modal --}}
        <div x-show="ingredientModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-xs">
            <div @click.outside="ingredientModalOpen = false" class="w-full max-w-md rounded-2xl border border-border bg-card p-6 shadow-2xl">
                <h2 class="mb-4 text-lg font-bold" x-text="editingIngredient ? 'Edit Bahan Baku' : 'Tambah Bahan Baku'"></h2>
                <form
                    method="POST"
                    :action="editingIngredient ? `/admin/inventory/ingredients/${editingIngredient.id}` : '{{ route('admin.inventory.ingredients.store') }}'"
                    class="space-y-4"
                >
                    @csrf
                    <template x-if="editingIngredient">
                        <input type="hidden" name="_method" value="PUT" />
                    </template>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-1.5">
                            <label class="text-xs font-semibold">Nama Bahan *</label>
                            <input type="text" name="name" x-model="ingForm.name" required class="h-10 rounded-lg border border-input bg-background px-3 text-sm" />
                        </div>
                        <div class="grid gap-1.5">
                            <label class="text-xs font-semibold">SKU</label>
                            <input type="text" name="sku" x-model="ingForm.sku" class="h-10 rounded-lg border border-input bg-background px-3 text-sm" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-1.5">
                            <label class="text-xs font-semibold">Satuan *</label>
                            <select name="unit" x-model="ingForm.unit" class="h-10 rounded-lg border border-input bg-background px-3 text-sm">
                                <option value="g">Gram (g)</option>
                                <option value="ml">Mililiter (ml)</option>
                                <option value="pcs">Pcs</option>
                            </select>
                        </div>
                        <div class="grid gap-1.5">
                            <label class="text-xs font-semibold">Harga per Satuan (Rp) *</label>
                            <input type="number" name="cost_per_unit" min="0" x-model.number="ingForm.cost_per_unit" required class="h-10 rounded-lg border border-input bg-background px-3 font-money text-sm" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <template x-if="!editingIngredient">
                            <div class="grid gap-1.5">
                                <label class="text-xs font-semibold">Stok Awal *</label>
                                <input type="number" step="0.001" min="0" name="current_stock" x-model.number="ingForm.current_stock" required class="h-10 rounded-lg border border-input bg-background px-3 font-money text-sm" />
                            </div>
                        </template>
                        <div class="grid gap-1.5" :class="editingIngredient ? 'col-span-2' : ''">
                            <label class="text-xs font-semibold">Batas Minimum Stok *</label>
                            <input type="number" step="0.001" min="0" name="min_stock" x-model.number="ingForm.min_stock" required class="h-10 rounded-lg border border-input bg-background px-3 font-money text-sm" />
                        </div>
                    </div>

                    <div class="grid gap-1.5">
                        <label class="text-xs font-semibold">Distributor Default</label>
                        <select name="default_distributor_id" x-model="ingForm.default_distributor_id" class="h-10 rounded-lg border border-input bg-background px-3 text-sm">
                            <option value="">Tidak ada</option>
                            @foreach ($distributors as $dist)
                                <option value="{{ $dist['id'] }}">{{ $dist['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <label class="flex items-center gap-2 text-xs cursor-pointer">
                        <input type="hidden" name="is_active" value="0" />
                        <input type="checkbox" name="is_active" value="1" x-model="ingForm.is_active" class="accent-primary" />
                        <span>Bahan Aktif</span>
                    </label>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="ingredientModalOpen = false" class="h-10 rounded-xl border border-border px-4 text-sm">Batal</button>
                        <button type="submit" class="h-10 rounded-xl bg-crema px-5 text-sm font-bold text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Restock Modal --}}
        <div x-show="restockModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-xs">
            <div @click.outside="restockModalOpen = false" class="w-full max-w-md rounded-2xl border border-border bg-card p-6 shadow-2xl">
                <h2 class="mb-4 text-lg font-bold">Stock In: <span x-text="targetIngredient?.name"></span></h2>
                <form method="POST" :action="targetIngredient ? `/admin/inventory/ingredients/${targetIngredient.id}/restock` : '#'" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-1.5">
                            <label class="text-xs font-semibold">Jumlah Masuk (<span x-text="targetIngredient?.unit"></span>) *</label>
                            <input type="number" step="0.001" min="0.001" name="quantity" x-model="restockForm.quantity" required class="h-10 rounded-lg border border-input bg-background px-3 font-money text-sm" />
                        </div>
                        <div class="grid gap-1.5">
                            <label class="text-xs font-semibold">Total Biaya Beli (Rp) *</label>
                            <input type="number" min="0" name="total_cost" x-model="restockForm.total_cost" required class="h-10 rounded-lg border border-input bg-background px-3 font-money text-sm" />
                        </div>
                    </div>
                    <div class="grid gap-1.5">
                        <label class="text-xs font-semibold">Distributor</label>
                        <select name="distributor_id" x-model="restockForm.distributor_id" class="h-10 rounded-lg border border-input bg-background px-3 text-sm">
                            <option value="">Pembelian Langsung</option>
                            @foreach ($distributors as $dist)
                                <option value="{{ $dist['id'] }}">{{ $dist['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid gap-1.5">
                        <label class="text-xs font-semibold">Catatan</label>
                        <input type="text" name="notes" x-model="restockForm.notes" placeholder="No. Nota / PO..." class="h-10 rounded-lg border border-input bg-background px-3 text-sm" />
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="restockModalOpen = false" class="h-10 rounded-xl border border-border px-4 text-sm">Batal</button>
                        <button type="submit" class="h-10 rounded-xl bg-emerald-600 px-5 text-sm font-bold text-white">Simpan Stock In</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Waste Modal --}}
        <div x-show="wasteModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-xs">
            <div @click.outside="wasteModalOpen = false" class="w-full max-w-md rounded-2xl border border-border bg-card p-6 shadow-2xl">
                <h2 class="mb-4 text-lg font-bold text-red-400">Catat Waste: <span x-text="targetIngredient?.name"></span></h2>
                <form method="POST" :action="targetIngredient ? `/admin/inventory/ingredients/${targetIngredient.id}/waste` : '#'" class="space-y-4">
                    @csrf
                    <div class="grid gap-1.5">
                        <label class="text-xs font-semibold">Jumlah Rusak / Terbuang (<span x-text="targetIngredient?.unit"></span>) *</label>
                        <input type="number" step="0.001" min="0.001" name="quantity" x-model="wasteForm.quantity" required class="h-10 rounded-lg border border-input bg-background px-3 font-money text-sm" />
                    </div>
                    <div class="grid gap-1.5">
                        <label class="text-xs font-semibold">Alasan *</label>
                        <input type="text" name="reason" x-model="wasteForm.reason" required placeholder="Contoh: Tumpah / Kadaluarsa" class="h-10 rounded-lg border border-input bg-background px-3 text-sm" />
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="wasteModalOpen = false" class="h-10 rounded-xl border border-border px-4 text-sm">Batal</button>
                        <button type="submit" class="h-10 rounded-xl bg-red-600 px-5 text-sm font-bold text-white">Simpan Waste</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Opname Modal --}}
        <div x-show="opnameModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-xs">
            <div @click.outside="opnameModalOpen = false" class="w-full max-w-md rounded-2xl border border-border bg-card p-6 shadow-2xl">
                <h2 class="mb-4 text-lg font-bold text-amber-400">Stock Opname: <span x-text="targetIngredient?.name"></span></h2>
                <form method="POST" :action="targetIngredient ? `/admin/inventory/ingredients/${targetIngredient.id}/opname` : '#'" class="space-y-4">
                    @csrf
                    <div class="grid gap-1.5">
                        <label class="text-xs font-semibold">Stok Fisik Aktual (<span x-text="targetIngredient?.unit"></span>) *</label>
                        <input type="number" step="0.001" min="0" name="actual_stock" x-model="opnameForm.actual_stock" required class="h-10 rounded-lg border border-input bg-background px-3 font-money text-sm" />
                    </div>
                    <div class="grid gap-1.5">
                        <label class="text-xs font-semibold">Catatan Opname *</label>
                        <input type="text" name="reason" x-model="opnameForm.reason" required placeholder="Contoh: Cek fisik akhir shift" class="h-10 rounded-lg border border-input bg-background px-3 text-sm" />
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="opnameModalOpen = false" class="h-10 rounded-xl border border-border px-4 text-sm">Batal</button>
                        <button type="submit" class="h-10 rounded-xl bg-amber-600 px-5 text-sm font-bold text-white">Simpan Opname</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.rail>
