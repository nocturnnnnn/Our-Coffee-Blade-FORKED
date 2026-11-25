<x-layouts.rail title="Karyawan & PIN">
    <div x-data="adminStaffApp()" class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Karyawan & Otorisasi PIN</h1>
                <p class="mt-1 text-sm text-muted-foreground">Kelola akun Admin, Kasir, Barista, serta PIN otorisasi supervisor 6 digit.</p>
            </div>
            <button
                type="button"
                @click="openCreate()"
                class="inline-flex h-10 items-center gap-2 rounded-xl bg-crema px-4 text-sm font-bold text-white hover:bg-crema/90"
            >
                <x-icon name="user-plus" class="size-4" />
                <span>Tambah Karyawan</span>
            </button>
        </div>

        <div class="rounded-2xl border border-border bg-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-border bg-muted/50 text-xs text-muted-foreground uppercase">
                        <tr>
                            <th class="px-4 py-3">Nama & Email</th>
                            <th class="px-4 py-3">Role</th>
                            <th class="px-4 py-3 text-center">PIN Otorisasi</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach ($staff as $person)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold">{{ $person['name'] }}</div>
                                    <div class="text-xs text-muted-foreground">{{ $person['email'] }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'rounded-lg px-2.5 py-0.5 text-xs font-bold uppercase',
                                        'bg-crema/20 text-amber-400' => $person['role'] === 'admin',
                                        'bg-sky-500/15 text-sky-400' => $person['role'] === 'kasir',
                                        'bg-emerald-500/15 text-emerald-400' => $person['role'] === 'barista',
                                    ])>
                                        {{ $person['role_label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span @class([
                                        'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                        'bg-emerald-500/15 text-emerald-400' => $person['has_pin'],
                                        'bg-muted text-muted-foreground' => ! $person['has_pin'],
                                    ])>
                                        <x-icon :name="$person['has_pin'] ? 'shield-check' : 'shield-alert'" class="size-3.5" />
                                        <span>{{ $person['has_pin'] ? 'Terpasang' : 'Belum Set' }}</span>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span @class([
                                        'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                        'bg-emerald-500/15 text-emerald-400' => $person['is_active'],
                                        'bg-red-500/15 text-red-400' => ! $person['is_active'],
                                    ])>
                                        {{ $person['is_active'] ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            @click='openPinModal(@json($person))'
                                            class="inline-flex h-8 items-center gap-1 rounded-lg border border-amber-500/30 bg-amber-500/10 px-2.5 text-xs font-semibold text-amber-400 hover:bg-amber-500/20"
                                        >
                                            <x-icon name="key-round" class="size-3.5" />
                                            <span>PIN</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click='openEdit(@json($person))'
                                            class="inline-flex h-8 items-center gap-1 rounded-lg border border-border px-2.5 text-xs hover:bg-muted"
                                        >
                                            <x-icon name="edit-2" class="size-3.5" />
                                            <span>Edit</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Create / Edit Modal --}}
        <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-xs">
            <div @click.outside="modalOpen = false" class="w-full max-w-md rounded-2xl border border-border bg-card p-6 shadow-2xl">
                <h2 class="mb-4 text-lg font-bold" x-text="editing ? 'Edit Akun Karyawan' : 'Tambah Akun Karyawan'"></h2>
                <form
                    method="POST"
                    :action="editing ? `/admin/staff/${editing.id}` : '{{ route('admin.staff.store') }}'"
                    class="space-y-4"
                >
                    @csrf
                    <template x-if="editing">
                        <input type="hidden" name="_method" value="PUT" />
                    </template>

                    <div class="grid gap-1.5">
                        <label class="text-xs font-semibold">Nama Lengkap *</label>
                        <input type="text" name="name" x-model="form.name" required class="h-10 rounded-lg border border-input bg-background px-3 text-sm" />
                    </div>
                    <div class="grid gap-1.5">
                        <label class="text-xs font-semibold">Email Login *</label>
                        <input type="email" name="email" x-model="form.email" required class="h-10 rounded-lg border border-input bg-background px-3 text-sm" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-1.5">
                            <label class="text-xs font-semibold">Role *</label>
                            <select name="role" x-model="form.role" class="h-10 rounded-lg border border-input bg-background px-3 text-sm">
                                <option value="admin">Admin</option>
                                <option value="kasir">Kasir</option>
                                <option value="barista">Barista</option>
                            </select>
                        </div>
                        <div class="grid gap-1.5">
                            <label class="text-xs font-semibold" x-text="editing ? 'Password Baru (Opsional)' : 'Password *'"></label>
                            <input type="password" name="password" x-model="form.password" :required="!editing" minlength="8" class="h-10 rounded-lg border border-input bg-background px-3 text-sm" />
                        </div>
                    </div>

                    <template x-if="!editing">
                        <div class="grid gap-1.5">
                            <label class="text-xs font-semibold">PIN Supervisor 6 Digit (Opsional)</label>
                            <input type="password" name="pin" x-model="form.pin" maxlength="6" inputmode="numeric" class="h-10 rounded-lg border border-input bg-background px-3 font-money text-sm" />
                        </div>
                    </template>

                    <label class="flex items-center gap-2 text-xs cursor-pointer">
                        <input type="hidden" name="is_active" value="0" />
                        <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="accent-primary" />
                        <span>Akun Aktif</span>
                    </label>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="modalOpen = false" class="h-10 rounded-xl border border-border px-4 text-sm">Batal</button>
                        <button type="submit" class="h-10 rounded-xl bg-crema px-5 text-sm font-bold text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- PIN Modal --}}
        <div x-show="pinModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-xs">
            <div @click.outside="pinModalOpen = false" class="w-full max-w-sm rounded-2xl border border-border bg-card p-6 shadow-2xl">
                <h2 class="mb-1 text-lg font-bold">Atur PIN: <span x-text="pinTarget?.name"></span></h2>
                <p class="mb-4 text-xs text-muted-foreground">Masukkan 6 digit angka, atau kosongkan untuk mencabut PIN otorisasi.</p>

                <form method="POST" :action="pinTarget ? `/admin/staff/${pinTarget.id}/pin` : '#'" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <input type="password" name="pin" x-model="pinValue" maxlength="6" inputmode="numeric" placeholder="6 digit angka" class="h-11 w-full rounded-lg border border-input bg-background px-3 text-center font-money text-lg tracking-widest" />
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="pinModalOpen = false" class="h-10 rounded-xl border border-border px-4 text-sm">Batal</button>
                        <button type="submit" class="h-10 rounded-xl bg-crema px-5 text-sm font-bold text-white">Simpan PIN</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.rail>
