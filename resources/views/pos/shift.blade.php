<x-layouts.rail title="Shift Kasir">
    <script type="application/json" id="pos-shift-data">
        {!! json_encode([
            'expectedCash' => $activeShiftDetail['expected_cash'] ?? 0,
        ], JSON_HEX_TAG) !!}
    </script>

    <div x-data="posShiftApp('pos-shift-data')" class="mx-auto max-w-5xl space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Shift Kasir & Rekonsiliasi Kas</h1>
                <p class="mt-1 text-sm text-muted-foreground">Buka laci kasir sebelum menerima transaksi dan hitung uang tunai saat tutup shift.</p>
            </div>
            @if ($activeShiftDetail !== null)
                <a href="{{ route('pos.index') }}" class="inline-flex h-10 items-center gap-2 rounded-xl bg-crema px-4 text-sm font-bold text-white hover:bg-crema/90">
                    <x-icon name="receipt" class="size-4" />
                    <span>Buka Layar POS Kasir</span>
                </a>
            @endif
        </div>

        @if ($activeShiftDetail === null)
            {{-- Open Shift Card --}}
            <div class="max-w-lg rounded-2xl border border-border bg-card p-6 shadow-sm">
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex size-11 items-center justify-center rounded-xl bg-crema/15 text-amber-500">
                        <x-icon name="wallet" class="size-5" />
                    </div>
                    <div>
                        <h2 class="text-lg font-bold">Buka Shift Baru</h2>
                        <p class="text-xs text-muted-foreground">Masukkan modal awal uang tunai (petty cash) di laci kasir.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('pos.shift.open') }}" class="space-y-4">
                    @csrf
                    <div class="grid gap-2">
                        <label for="opening_cash" class="text-sm font-medium">Modal Awal Tunai (Rp)</label>
                        <input
                            id="opening_cash"
                            name="opening_cash"
                            type="number"
                            min="0"
                            step="1000"
                            value="{{ old('opening_cash', 300000) }}"
                            required
                            class="flex h-11 w-full rounded-xl border border-input bg-background px-3.5 font-money text-base font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        />
                        @error('opening_cash')
                            <p class="text-xs text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-crema text-sm font-bold text-white hover:bg-crema/90">
                        <x-icon name="unlock" class="size-4" />
                        <span>Buka Shift Kasir</span>
                    </button>
                </form>
            </div>
        @else
            {{-- Active Shift Summary & Close Form --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="rounded-2xl border border-border bg-card p-6 space-y-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="rounded-full bg-emerald-500/15 px-2.5 py-0.5 text-xs font-bold text-emerald-400">
                                Shift Aktif #{{ $activeShiftDetail['id'] }}
                            </span>
                            <div class="mt-2 text-xs text-muted-foreground">
                                Dibuka {{ \Carbon\Carbon::parse($activeShiftDetail['opened_at'])->setTimezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-muted-foreground">Transaksi</div>
                            <div class="font-money text-2xl font-bold">{{ $activeShiftDetail['orders_count'] }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl border border-border bg-background/60 p-3.5">
                            <div class="text-xs text-muted-foreground">Modal Awal</div>
                            <div class="mt-1 font-money text-base font-bold">Rp {{ number_format($activeShiftDetail['opening_cash'], 0, ',', '.') }}</div>
                        </div>
                        <div class="rounded-xl border border-border bg-background/60 p-3.5">
                            <div class="text-xs text-muted-foreground">Penjualan Tunai</div>
                            <div class="mt-1 font-money text-base font-bold text-emerald-400">Rp {{ number_format($activeShiftDetail['cash_sales_total'], 0, ',', '.') }}</div>
                        </div>
                        <div class="rounded-xl border border-border bg-background/60 p-3.5">
                            <div class="text-xs text-muted-foreground">Non-Tunai (QRIS/EDC)</div>
                            <div class="mt-1 font-money text-base font-bold">Rp {{ number_format($activeShiftDetail['non_cash_sales_total'], 0, ',', '.') }}</div>
                        </div>
                        <div class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-3.5">
                            <div class="text-xs text-amber-400">Kas Diharapkan (Sistem)</div>
                            <div class="mt-1 font-money text-base font-extrabold text-amber-400">Rp {{ number_format($activeShiftDetail['expected_cash'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-border bg-card p-6">
                    <h2 class="text-lg font-bold">Tutup Shift & Setor Kas</h2>
                    <p class="mt-1 text-xs text-muted-foreground">Hitung seluruh uang fisik tunai di laci lalu masukkan nominal sebenarnya.</p>

                    <form method="POST" action="{{ route('pos.shift.close') }}" class="mt-4 space-y-4">
                        @csrf
                        <div class="grid gap-1.5">
                            <label for="actual_cash" class="text-sm font-medium">Uang Tunai Aktual (Rp)</label>
                            <input
                                id="actual_cash"
                                name="actual_cash"
                                type="number"
                                min="0"
                                x-model.number="actualCash"
                                required
                                class="flex h-11 w-full rounded-xl border border-input bg-background px-3.5 font-money text-base font-bold"
                            />
                            @error('actual_cash')
                                <p class="text-xs text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div
                            class="flex items-center justify-between rounded-xl border p-3.5 text-sm"
                            :class="variance === 0
                                ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400'
                                : 'border-amber-500/40 bg-amber-500/10 text-amber-400'"
                        >
                            <span class="font-medium">Selisih Kas (Aktual - Sistem)</span>
                            <span class="font-money font-bold" x-text="(variance >= 0 ? '+' : '') + window.ocFormatRupiah(variance)"></span>
                        </div>

                        <div class="grid gap-1.5">
                            <label for="variance_reason" class="text-sm font-medium">
                                Catatan / Alasan Selisih
                                <span x-show="variance !== 0" class="text-red-400">*</span>
                            </label>
                            <textarea
                                id="variance_reason"
                                name="variance_reason"
                                rows="2"
                                x-model="varianceReason"
                                :required="variance !== 0"
                                placeholder="Wajib diisi jika terdapat selisih kas..."
                                class="w-full rounded-xl border border-input bg-background p-3 text-sm"
                            ></textarea>
                            @error('variance_reason')
                                <p class="text-xs text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-red-600 text-sm font-bold text-white hover:bg-red-500">
                            <x-icon name="lock" class="size-4" />
                            <span>Tutup Shift Sekarang</span>
                        </button>
                    </form>
                </div>
            </div>
        @endif

        {{-- Recent Closed Shifts --}}
        <div class="rounded-2xl border border-border bg-card overflow-hidden">
            <div class="border-b border-border px-5 py-4">
                <h2 class="text-base font-bold">Riwayat Shift Tertutup</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-border bg-muted/50 text-xs text-muted-foreground uppercase">
                        <tr>
                            <th class="px-4 py-3">Kasir</th>
                            <th class="px-4 py-3">Buka / Tutup</th>
                            <th class="px-4 py-3 text-right">Modal</th>
                            <th class="px-4 py-3 text-right">Sistem</th>
                            <th class="px-4 py-3 text-right">Aktual</th>
                            <th class="px-4 py-3 text-right">Selisih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($recentShifts as $s)
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ $s['cashier_name'] }}</td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">
                                    {{ \Carbon\Carbon::parse($s['opened_at'])->setTimezone('Asia/Jakarta')->format('d/m H:i') }}
                                    →
                                    {{ $s['closed_at'] ? \Carbon\Carbon::parse($s['closed_at'])->setTimezone('Asia/Jakarta')->format('d/m H:i') : '-' }}
                                </td>
                                <td class="px-4 py-3 text-right font-money">Rp {{ number_format($s['opening_cash'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-money">Rp {{ number_format($s['expected_cash'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-money">Rp {{ number_format($s['actual_cash'], 0, ',', '.') }}</td>
                                <td @class([
                                    'px-4 py-3 text-right font-money font-semibold',
                                    'text-emerald-400' => $s['variance'] === 0,
                                    'text-red-400' => $s['variance'] < 0,
                                    'text-amber-400' => $s['variance'] > 0,
                                ])>
                                    {{ $s['variance'] > 0 ? '+' : '' }}Rp {{ number_format($s['variance'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">Belum ada riwayat shift tertutup.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.rail>
