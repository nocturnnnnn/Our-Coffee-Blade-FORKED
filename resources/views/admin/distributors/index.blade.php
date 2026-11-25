<x-layouts.rail title="Distributor">
    <div x-data="adminDistributorsApp()" class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Distributor & Supplier</h1>
                <p class="mt-1 text-sm text-muted-foreground">Kelola daftar pemasok biji kopi, susu, sirup, dan bahan baku kafe.</p>
            </div>
            <button
                type="button"
                @click="openCreate()"
                class="inline-flex h-10 items-center gap-2 rounded-xl bg-crema px-4 text-sm font-bold text-white hover:bg-crema/90"
            >
                <x-icon name="plus" class="size-4" />
                <span>Tambah Distributor</span>
            </button>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($distributors as $dist)
                <div class="flex flex-col justify-between rounded-2xl border border-border bg-card p-5 space-y-4">
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h2 class="text-base font-bold">{{ $dist['name'] }}</h2>
                                <p class="text-xs text-muted-foreground">{{ $dist['contact_person'] ?: 'Tanpa PIC' }}</p>
                            </div>
                            <span @class([
                                'rounded-full px-2.5 py-0.5 text-[11px] font-semibold',
                                'bg-emerald-500/15 text-emerald-400' => $dist['is_active'],
                                'bg-muted text-muted-foreground' => ! $dist['is_active'],
                            ])>
                                {{ $dist['is_active'] ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>

                        <div class="mt-4 space-y-1 text-xs text-muted-foreground">
                            <div>Telp: <span class="text-foreground">{{ $dist['phone'] ?: '-' }}</span></div>
                            <div>Email: <span class="text-foreground">{{ $dist['email'] ?: '-' }}</span></div>
                            <div>Alamat: <span class="text-foreground">{{ $dist['address'] ?: '-' }}</span></div>
                            @if ($dist['notes'])
                                <div class="italic text-amber-400/90">{{ $dist['notes'] }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center justify-between border-t border-border pt-3">
                        <span class="text-xs text-muted-foreground">{{ $dist['ingredients_count'] }} bahan terhubung</span>
                        <div class="inline-flex gap-1.5">
                            <button
                                type="button"
                                @click='openEdit(@json($dist))'
                                class="inline-flex h-8 items-center gap-1 rounded-lg border border-border px-2.5 text-xs hover:bg-muted"
                            >
                                <x-icon name="edit-2" class="size-3.5" />
                                <span>Edit</span>
                            </button>
                            <form method="POST" action="{{ route('admin.distributors.destroy', $dist['id']) }}" onsubmit="return confirm('Hapus distributor {{ addslashes($dist['name']) }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex h-8 items-center rounded-lg border border-red-500/30 bg-red-500/10 px-2.5 text-xs text-red-400">
                                    <x-icon name="trash-2" class="size-3.5" />
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full rounded-2xl border border-border bg-card py-12 text-center text-sm text-muted-foreground">
                    Belum ada data distributor.
                </div>
            @endforelse
        </div>

        {{-- Modal --}}
        <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-xs">
            <div @click.outside="modalOpen = false" class="w-full max-w-md rounded-2xl border border-border bg-card p-6 shadow-2xl">
                <h2 class="mb-4 text-lg font-bold" x-text="editing ? 'Edit Distributor' : 'Tambah Distributor'"></h2>
                <form
                    method="POST"
                    :action="editing ? `/admin/distributors/${editing.id}` : '{{ route('admin.distributors.store') }}'"
                    class="space-y-4"
                >
                    @csrf
                    <template x-if="editing">
                        <input type="hidden" name="_method" value="PUT" />
                    </template>

                    <div class="grid gap-1.5">
                        <label class="text-xs font-semibold">Nama Perusahaan / Supplier *</label>
                        <input type="text" name="name" x-model="form.name" required class="h-10 rounded-lg border border-input bg-background px-3 text-sm" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-1.5">
                            <label class="text-xs font-semibold">Nama Kontak (PIC)</label>
                            <input type="text" name="contact_person" x-model="form.contact_person" class="h-10 rounded-lg border border-input bg-background px-3 text-sm" />
                        </div>
                        <div class="grid gap-1.5">
                            <label class="text-xs font-semibold">Telepon / WA</label>
                            <input type="text" name="phone" x-model="form.phone" class="h-10 rounded-lg border border-input bg-background px-3 text-sm" />
                        </div>
                    </div>
                    <div class="grid gap-1.5">
                        <label class="text-xs font-semibold">Email</label>
                        <input type="email" name="email" x-model="form.email" class="h-10 rounded-lg border border-input bg-background px-3 text-sm" />
                    </div>
                    <div class="grid gap-1.5">
                        <label class="text-xs font-semibold">Alamat</label>
                        <textarea name="address" rows="2" x-model="form.address" class="rounded-lg border border-input bg-background p-3 text-sm"></textarea>
                    </div>
                    <div class="grid gap-1.5">
                        <label class="text-xs font-semibold">Catatan</label>
                        <input type="text" name="notes" x-model="form.notes" class="h-10 rounded-lg border border-input bg-background px-3 text-sm" />
                    </div>

                    <label class="flex items-center gap-2 text-xs cursor-pointer">
                        <input type="hidden" name="is_active" value="0" />
                        <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="accent-primary" />
                        <span>Distributor Aktif</span>
                    </label>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="modalOpen = false" class="h-10 rounded-xl border border-border px-4 text-sm">Batal</button>
                        <button type="submit" class="h-10 rounded-xl bg-crema px-5 text-sm font-bold text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.rail>
