<x-layouts.rail title="Pengaturan Kafe & Pairing Kiosk">
    <div x-data="adminSettingsApp()" class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Pengaturan Kafe & Pairing Kiosk</h1>
            <p class="mt-1 text-sm text-muted-foreground">Atur identitas struk thermal, durasi keamanan Kiosk, dan perangkat Kiosk terpasang.</p>
        </div>

        {{-- Store Identity & Operational Params --}}
        <section class="rounded-2xl border border-border bg-card p-6">
            <h2 class="mb-4 text-base font-bold">Identitas Kafe & Parameter Operasional</h2>

            <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="grid gap-1.5">
                        <label for="cafe_name" class="text-xs font-semibold">Nama Kafe</label>
                        <input id="cafe_name" name="cafe_name" type="text" value="{{ old('cafe_name', $settings['cafe_name']) }}" required class="h-10 rounded-lg border border-input bg-background px-3 text-sm" />
                    </div>
                    <div class="grid gap-1.5">
                        <label for="cafe_phone" class="text-xs font-semibold">Telepon</label>
                        <input id="cafe_phone" name="cafe_phone" type="text" value="{{ old('cafe_phone', $settings['cafe_phone']) }}" class="h-10 rounded-lg border border-input bg-background px-3 text-sm" />
                    </div>
                </div>

                <div class="grid gap-1.5">
                    <label for="cafe_address" class="text-xs font-semibold">Alamat Struk</label>
                    <input id="cafe_address" name="cafe_address" type="text" value="{{ old('cafe_address', $settings['cafe_address']) }}" class="h-10 rounded-lg border border-input bg-background px-3 text-sm" />
                </div>

                <div class="grid gap-1.5">
                    <label for="receipt_footer" class="text-xs font-semibold">Footer Struk</label>
                    <input id="receipt_footer" name="receipt_footer" type="text" value="{{ old('receipt_footer', $settings['receipt_footer']) }}" class="h-10 rounded-lg border border-input bg-background px-3 text-sm" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="grid gap-1.5">
                        <label for="kiosk_idle_timeout_seconds" class="text-xs font-semibold">Idle Timeout (detik)</label>
                        <input id="kiosk_idle_timeout_seconds" name="kiosk_idle_timeout_seconds" type="number" min="30" max="600" value="{{ old('kiosk_idle_timeout_seconds', $settings['kiosk_idle_timeout_seconds']) }}" required class="h-10 rounded-lg border border-input bg-background px-3 font-money text-sm" />
                    </div>
                    <div class="grid gap-1.5">
                        <label for="kiosk_idle_warning_seconds" class="text-xs font-semibold">Peringatan Idle (detik)</label>
                        <input id="kiosk_idle_warning_seconds" name="kiosk_idle_warning_seconds" type="number" min="10" value="{{ old('kiosk_idle_warning_seconds', $settings['kiosk_idle_warning_seconds']) }}" required class="h-10 rounded-lg border border-input bg-background px-3 font-money text-sm" />
                    </div>
                    <div class="grid gap-1.5">
                        <label for="kiosk_confirmation_reset_seconds" class="text-xs font-semibold">Reset Konfirmasi (detik)</label>
                        <input id="kiosk_confirmation_reset_seconds" name="kiosk_confirmation_reset_seconds" type="number" min="3" max="60" value="{{ old('kiosk_confirmation_reset_seconds', $settings['kiosk_confirmation_reset_seconds']) }}" required class="h-10 rounded-lg border border-input bg-background px-3 font-money text-sm" />
                    </div>
                    <div class="grid gap-1.5">
                        <label for="qris_expiry_seconds" class="text-xs font-semibold">Masa Berlaku QRIS (detik)</label>
                        <input id="qris_expiry_seconds" name="qris_expiry_seconds" type="number" min="60" max="900" value="{{ old('qris_expiry_seconds', $settings['qris_expiry_seconds']) }}" required class="h-10 rounded-lg border border-input bg-background px-3 font-money text-sm" />
                    </div>
                </div>

                @if ($errors->any())
                    <p class="text-xs text-destructive">{{ $errors->first() }}</p>
                @endif

                <div class="pt-2">
                    <button type="submit" class="inline-flex h-10 items-center justify-center rounded-xl bg-amber-500 px-5 text-sm font-bold text-espresso hover:bg-amber-400">
                        Simpan Pengaturan
                    </button>
                </div>
            </form>
        </section>

        {{-- Paired Kiosk Devices --}}
        <section class="rounded-2xl border border-border bg-card p-6 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold">Perangkat Self-Ordering Kiosk</h2>
                    <p class="text-xs text-muted-foreground">Buka tautan pairing pada tablet/layar Kiosk untuk menanamkan token perangkat.</p>
                </div>

                <form method="POST" action="{{ route('admin.settings.devices.store') }}" class="flex items-center gap-2">
                    @csrf
                    <input type="text" name="name" placeholder="Nama perangkat (mis. Kiosk 2)" required class="h-10 w-56 rounded-xl border border-input bg-background px-3 text-xs" />
                    <button type="submit" class="inline-flex h-10 items-center gap-1.5 rounded-xl bg-amber-500 px-4 text-xs font-bold text-espresso hover:bg-amber-400">
                        <x-icon name="plus" class="size-4" />
                        <span>Tambah Kiosk</span>
                    </button>
                </form>
            </div>

            <div class="space-y-3">
                @forelse ($devices as $dev)
                    <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-border bg-background/60 p-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold">{{ $dev['name'] }}</span>
                                <span @class([
                                    'rounded px-2 py-0.5 text-[10px] font-bold',
                                    'bg-emerald-500/15 text-emerald-400' => $dev['is_active'],
                                    'bg-muted text-muted-foreground' => ! $dev['is_active'],
                                ])>
                                    {{ $dev['is_active'] ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                            <div class="mt-1 font-mono text-xs text-muted-foreground">
                                Token: {{ $dev['device_token'] }}
                            </div>
                            <div class="text-[11px] text-muted-foreground">
                                Terakhir aktif: {{ $dev['last_seen_at'] ? \Carbon\Carbon::parse($dev['last_seen_at'])->setTimezone('Asia/Jakarta')->format('d M Y H:i').' WIB' : 'Belum pernah terhubung' }}
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                @click="copyPairUrl('{{ $dev['pair_url'] }}')"
                                class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-border bg-card px-3 text-xs font-medium hover:bg-muted"
                            >
                                <x-icon name="copy" class="size-3.5" />
                                <span>Salin Link Pair</span>
                            </button>

                            <a
                                href="{{ $dev['pair_url'] }}"
                                target="_blank"
                                class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-border bg-card px-3 text-xs font-medium hover:bg-muted"
                            >
                                <x-icon name="external-link" class="size-3.5" />
                                <span>Buka Kiosk</span>
                            </a>

                            <form method="POST" action="{{ route('admin.settings.devices.rotate', $dev['id']) }}">
                                @csrf
                                <button type="submit" title="Rotasi Token" class="inline-flex size-9 items-center justify-center rounded-lg border border-border bg-card hover:bg-muted">
                                    <x-icon name="refresh-cw" class="size-3.5" />
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.settings.devices.toggle', $dev['id']) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" title="Aktifkan/Nonaktifkan" class="inline-flex size-9 items-center justify-center rounded-lg border border-border bg-card hover:bg-muted">
                                    <x-icon name="power" class="size-3.5" />
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.settings.devices.destroy', $dev['id']) }}" onsubmit="return confirm('Hapus perangkat {{ addslashes($dev['name']) }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Hapus Perangkat" class="inline-flex size-9 items-center justify-center rounded-lg border border-red-500/30 bg-red-500/10 text-red-400 hover:bg-red-500/20">
                                    <x-icon name="trash-2" class="size-3.5" />
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-xs text-muted-foreground">Belum ada perangkat Kiosk terdaftar.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts.rail>
