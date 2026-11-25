@props(['passwordRules' => null])

<x-layouts.settings title="Keamanan & Kata Sandi">
    <section class="rounded-2xl border border-border bg-card p-6">
        <div class="mb-5">
            <h2 class="text-base font-semibold">Perbarui Kata Sandi</h2>
            <p class="text-sm text-muted-foreground">Pastikan akun Anda menggunakan kata sandi yang panjang dan aman.</p>
        </div>

        <form method="POST" action="{{ route('user-password.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid gap-2 max-w-md">
                <label for="current_password" class="text-sm font-medium">Kata Sandi Saat Ini</label>
                <x-password-input
                    id="current_password"
                    name="current_password"
                    required
                    autocomplete="current-password"
                    placeholder="Kata sandi saat ini"
                />
                @error('current_password')
                    <p class="text-xs text-destructive">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-2 max-w-md">
                <label for="password" class="text-sm font-medium">Kata Sandi Baru</label>
                <x-password-input
                    id="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder="Kata sandi baru"
                />
                @if ($passwordRules)
                    <p class="text-xs text-muted-foreground">{{ $passwordRules }}</p>
                @endif
                @error('password')
                    <p class="text-xs text-destructive">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-2 max-w-md">
                <label for="password_confirmation" class="text-sm font-medium">Konfirmasi Kata Sandi Baru</label>
                <x-password-input
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Ulangi kata sandi baru"
                />
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button
                    type="submit"
                    class="inline-flex h-9 items-center justify-center rounded-lg bg-primary px-4 text-sm font-semibold text-primary-foreground hover:bg-primary/90"
                >
                    Simpan Kata Sandi
                </button>
                @if (session('success'))
                    <span class="text-sm text-emerald-500">{{ session('success') }}</span>
                @endif
            </div>
        </form>
    </section>
</x-layouts.settings>
