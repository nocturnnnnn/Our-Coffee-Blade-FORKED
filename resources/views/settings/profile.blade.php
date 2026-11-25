@props(['mustVerifyEmail' => false, 'status' => null])

@php
    $user = auth()->user();
@endphp

<x-layouts.settings title="Profil">
    <section class="rounded-2xl border border-border bg-card p-6">
        <div class="mb-5">
            <h2 class="text-base font-semibold">Informasi Profil</h2>
            <p class="text-sm text-muted-foreground">Perbarui nama dan alamat email akun Anda.</p>
        </div>

        <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
            @csrf
            @method('PATCH')

            <div class="grid gap-2">
                <label for="name" class="text-sm font-medium">Nama</label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name', $user?->name) }}"
                    required
                    autocomplete="name"
                    class="flex h-10 w-full max-w-md rounded-lg border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                />
                @error('name')
                    <p class="text-xs text-destructive">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-2">
                <label for="email" class="text-sm font-medium">Alamat Email</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email', $user?->email) }}"
                    required
                    autocomplete="username"
                    class="flex h-10 w-full max-w-md rounded-lg border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                />
                @error('email')
                    <p class="text-xs text-destructive">{{ $message }}</p>
                @enderror

                @if ($mustVerifyEmail && $user && ! $user->hasVerifiedEmail())
                    <p class="text-xs text-amber-500">Email Anda belum terverifikasi.</p>
                @endif
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button
                    type="submit"
                    class="inline-flex h-9 items-center justify-center rounded-lg bg-primary px-4 text-sm font-semibold text-primary-foreground hover:bg-primary/90"
                >
                    Simpan Profil
                </button>
                @if (session('success'))
                    <span class="text-sm text-emerald-500">{{ session('success') }}</span>
                @endif
            </div>
        </form>
    </section>

    <section x-data="{ confirmDelete: false }" class="rounded-2xl border border-destructive/30 bg-destructive/5 p-6">
        <div class="mb-4">
            <h2 class="text-base font-semibold text-destructive">Hapus Akun</h2>
            <p class="text-sm text-muted-foreground">Setelah akun dihapus, semua data terkait akan dihapus permanen.</p>
        </div>

        <button
            type="button"
            x-show="!confirmDelete"
            @click="confirmDelete = true"
            class="inline-flex h-9 items-center justify-center rounded-lg bg-destructive px-4 text-sm font-semibold text-destructive-foreground hover:bg-destructive/90"
        >
            Hapus Akun
        </button>

        <form x-show="confirmDelete" x-cloak method="POST" action="{{ route('profile.destroy') }}" class="space-y-4">
            @csrf
            @method('DELETE')

            <div class="grid gap-2 max-w-md">
                <label for="delete_password" class="text-sm font-medium">Konfirmasi Kata Sandi</label>
                <x-password-input
                    id="delete_password"
                    name="password"
                    required
                    placeholder="Masukkan kata sandi Anda"
                />
                @error('password')
                    <p class="text-xs text-destructive">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3">
                <button
                    type="submit"
                    class="inline-flex h-9 items-center justify-center rounded-lg bg-destructive px-4 text-sm font-semibold text-destructive-foreground hover:bg-destructive/90"
                >
                    Ya, Hapus Akun Permanen
                </button>
                <button
                    type="button"
                    @click="confirmDelete = false"
                    class="inline-flex h-9 items-center justify-center rounded-lg border border-border bg-card px-4 text-sm font-medium hover:bg-muted"
                >
                    Batal
                </button>
            </div>
        </form>
    </section>
</x-layouts.settings>
