<x-layouts.rail title="Laporan Penjualan & Laba">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Laporan Penjualan & Laba Kotor</h1>
                <p class="mt-1 text-sm text-muted-foreground">Rekapitulasi omzet bersih, diskon, HPP (COGS), dan margin keuntungan.</p>
            </div>
            <a
                href="{{ route('admin.reports.export', ['start_date' => $filters['start_date'], 'end_date' => $filters['end_date']]) }}"
                class="inline-flex h-10 items-center gap-2 rounded-xl bg-emerald-600 px-4 text-sm font-bold text-white hover:bg-emerald-500"
            >
                <x-icon name="download" class="size-4" />
                <span>Ekspor CSV</span>
            </a>
        </div>

        {{-- Date Range Filter --}}
        <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-wrap items-end gap-3 rounded-2xl border border-border bg-card p-4">
            <div class="grid gap-1">
                <label class="text-xs font-medium text-muted-foreground">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ $filters['start_date'] }}" class="h-9 rounded-lg border border-input bg-background px-3 text-sm" />
            </div>
            <div class="grid gap-1">
                <label class="text-xs font-medium text-muted-foreground">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ $filters['end_date'] }}" class="h-9 rounded-lg border border-input bg-background px-3 text-sm" />
            </div>
            <button type="submit" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-crema px-4 text-xs font-bold text-white">
                <x-icon name="filter" class="size-3.5" />
                <span>Tampilkan</span>
            </button>
        </form>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-2xl border border-border bg-card p-4">
                <div class="text-xs text-muted-foreground">Omzet Bersih (Net Revenue)</div>
                <div class="mt-1 font-money text-xl font-extrabold text-amber-500">Rp {{ number_format($summary['net_revenue'], 0, ',', '.') }}</div>
                <div class="mt-1 text-[11px] text-muted-foreground">Diskon: Rp {{ number_format($summary['total_discount'], 0, ',', '.') }}</div>
            </div>
            <div class="rounded-2xl border border-border bg-card p-4">
                <div class="text-xs text-muted-foreground">Total HPP (COGS)</div>
                <div class="mt-1 font-money text-xl font-extrabold">Rp {{ number_format($summary['total_cogs'], 0, ',', '.') }}</div>
                <div class="mt-1 text-[11px] text-muted-foreground">Penjualan kotor: Rp {{ number_format($summary['gross_sales'], 0, ',', '.') }}</div>
            </div>
            <div class="rounded-2xl border border-border bg-card p-4">
                <div class="text-xs text-muted-foreground">Laba Kotor (Gross Profit)</div>
                <div class="mt-1 font-money text-xl font-extrabold text-emerald-400">Rp {{ number_format($summary['gross_profit'], 0, ',', '.') }}</div>
                <div class="mt-1 text-[11px] text-emerald-400">Margin {{ $summary['gross_margin_percent'] }}%</div>
            </div>
            <div class="rounded-2xl border border-border bg-card p-4">
                <div class="text-xs text-muted-foreground">Order Selesai / Void</div>
                <div class="mt-1 font-money text-xl font-extrabold">{{ $summary['orders_count'] }}</div>
                <div class="mt-1 text-[11px] text-red-400">{{ $summary['voided_count'] }} order di-void</div>
            </div>
        </div>

        {{-- Daily Breakdown --}}
        <div class="rounded-2xl border border-border bg-card overflow-hidden">
            <div class="border-b border-border px-5 py-4">
                <h2 class="text-base font-bold">Ringkasan Harian</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-border bg-muted/50 text-xs text-muted-foreground uppercase">
                        <tr>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3 text-right">Order</th>
                            <th class="px-4 py-3 text-right">Omzet Bersih</th>
                            <th class="px-4 py-3 text-right">HPP</th>
                            <th class="px-4 py-3 text-right">Laba Kotor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($dailyRows as $row)
                            <tr>
                                <td class="px-4 py-3 font-money">{{ $row['date'] }}</td>
                                <td class="px-4 py-3 text-right font-money">{{ $row['orders'] }}</td>
                                <td class="px-4 py-3 text-right font-money font-semibold">Rp {{ number_format($row['net_revenue'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-money text-muted-foreground">Rp {{ number_format($row['cogs'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-money font-bold text-emerald-400">Rp {{ number_format($row['profit'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">Tidak ada transaksi pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Per-Menu Profitability --}}
        <div class="rounded-2xl border border-border bg-card overflow-hidden">
            <div class="border-b border-border px-5 py-4">
                <h2 class="text-base font-bold">Performa Penjualan Per Menu</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-border bg-muted/50 text-xs text-muted-foreground uppercase">
                        <tr>
                            <th class="px-4 py-3">Menu</th>
                            <th class="px-4 py-3 text-right">Terjual</th>
                            <th class="px-4 py-3 text-right">Omzet</th>
                            <th class="px-4 py-3 text-right">HPP</th>
                            <th class="px-4 py-3 text-right">Laba Kotor</th>
                            <th class="px-4 py-3 text-right">Margin</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($menuRows as $row)
                            <tr>
                                <td class="px-4 py-3 font-semibold">{{ $row['name'] }}</td>
                                <td class="px-4 py-3 text-right font-money">{{ $row['qty_sold'] }}</td>
                                <td class="px-4 py-3 text-right font-money">Rp {{ number_format($row['revenue'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-money text-muted-foreground">Rp {{ number_format($row['cogs'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-money font-bold text-emerald-400">Rp {{ number_format($row['gross_profit'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-money font-bold text-emerald-400">{{ $row['margin_percent'] }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">Belum ada data penjualan menu.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.rail>
