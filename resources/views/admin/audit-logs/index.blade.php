<x-layouts.rail title="Audit Log">
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Audit Log Keamanan & Operasional</h1>
            <p class="mt-1 text-sm text-muted-foreground">Jejak aktivitas sensitif: Void, Diskon Manual, Selisih Kas Shift, Keluar Kiosk, dan Manajemen Akun.</p>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="flex flex-wrap items-end gap-3 rounded-2xl border border-border bg-card p-4">
            <div class="grid gap-1">
                <label class="text-xs font-medium text-muted-foreground">Aksi</label>
                <select name="action" class="h-9 rounded-lg border border-input bg-background px-3 text-xs">
                    <option value="">Semua Aksi</option>
                    @foreach ($actions as $act)
                        <option value="{{ $act }}" @selected($filters['action'] === $act)>{{ $act }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid gap-1">
                <label class="text-xs font-medium text-muted-foreground">Staf / Authorizer</label>
                <select name="actor_id" class="h-9 rounded-lg border border-input bg-background px-3 text-xs">
                    <option value="">Semua Staf</option>
                    @foreach ($staffList as $s)
                        <option value="{{ $s['id'] }}" @selected($filters['actor_id'] === $s['id'])>{{ $s['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid gap-1">
                <label class="text-xs font-medium text-muted-foreground">Dari</label>
                <input type="date" name="start_date" value="{{ $filters['start_date'] }}" class="h-9 rounded-lg border border-input bg-background px-3 text-xs" />
            </div>
            <div class="grid gap-1">
                <label class="text-xs font-medium text-muted-foreground">Sampai</label>
                <input type="date" name="end_date" value="{{ $filters['end_date'] }}" class="h-9 rounded-lg border border-input bg-background px-3 text-xs" />
            </div>
            <button type="submit" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-crema px-4 text-xs font-bold text-white">
                <x-icon name="filter" class="size-3.5" />
                <span>Filter</span>
            </button>
        </form>

        <div class="rounded-2xl border border-border bg-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-border bg-muted/50 text-muted-foreground uppercase">
                        <tr>
                            <th class="px-4 py-3">Waktu (WIB)</th>
                            <th class="px-4 py-3">Aksi</th>
                            <th class="px-4 py-3">Pelaku / Otorisator</th>
                            <th class="px-4 py-3">Objek</th>
                            <th class="px-4 py-3">Alasan & Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($logs as $log)
                            <tr>
                                <td class="px-4 py-3 font-money text-muted-foreground whitespace-nowrap">
                                    {{ $log['created_at'] ? \Carbon\Carbon::parse($log['created_at'])->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s') : '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded bg-muted px-2 py-0.5 font-mono font-semibold text-amber-400">{{ $log['action'] }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold">{{ $log['actor_name'] ?: '-' }}</div>
                                    @if ($log['authorizer_name'])
                                        <div class="text-[11px] text-amber-400">Auth PIN: {{ $log['authorizer_name'] }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono text-muted-foreground">
                                    {{ $log['auditable_type'] ? $log['auditable_type'].' #'.$log['auditable_id'] : '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div>{{ $log['reason'] ?: '-' }}</div>
                                    @if (! empty($log['metadata']) && is_array($log['metadata']))
                                        @php
                                            $labelMap = [
                                                'order_number' => 'Order',
                                                'subtotal' => 'Subtotal',
                                                'discount_amount' => 'Diskon',
                                                'total_amount' => 'Total',
                                                'opening_cash' => 'Modal Awal',
                                                'cash_sales_total' => 'Penjualan Tunai',
                                                'expected_cash' => 'Kas Sistem',
                                                'actual_cash' => 'Kas Aktual',
                                                'variance' => 'Selisih',
                                                'email' => 'Email',
                                                'role' => 'Role',
                                                'is_active' => 'Status',
                                                'quantity' => 'Jumlah',
                                                'stock_before' => 'Stok Awal',
                                                'stock_after' => 'Stok Akhir',
                                            ];
                                            $currencyKeys = [
                                                'subtotal',
                                                'discount_amount',
                                                'total_amount',
                                                'opening_cash',
                                                'cash_sales_total',
                                                'expected_cash',
                                                'actual_cash',
                                                'variance',
                                            ];
                                        @endphp
                                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                                            @foreach ($log['metadata'] as $key => $val)
                                                @php
                                                    $keyStr = (string) $key;
                                                    $label = $labelMap[$keyStr] ?? ucwords(str_replace('_', ' ', $keyStr));
                                                    if (is_bool($val)) {
                                                        $formatted = $keyStr === 'is_active'
                                                            ? ($val ? 'Aktif' : 'Nonaktif')
                                                            : ($val ? 'Ya' : 'Tidak');
                                                    } elseif (is_array($val)) {
                                                        $formatted = json_encode($val);
                                                    } elseif ($val === null || $val === '') {
                                                        $formatted = '-';
                                                    } elseif (in_array($keyStr, $currencyKeys, true) && is_numeric($val)) {
                                                        $num = (int) $val;
                                                        $prefix = $keyStr === 'variance' && $num > 0 ? '+' : ($keyStr === 'variance' && $num < 0 ? '-' : '');
                                                        $formatted = $prefix.'Rp '.number_format(abs($num), 0, ',', '.');
                                                    } else {
                                                        $formatted = (string) $val;
                                                    }
                                                @endphp
                                                <span class="inline-flex items-center gap-1 rounded-md border border-border bg-muted/60 px-2 py-0.5 text-[11px]">
                                                    <span class="text-muted-foreground">{{ $label }}:</span>
                                                    <span @class([
                                                        'font-money font-medium',
                                                        'text-amber-400' => $keyStr === 'discount_amount',
                                                        'text-red-400' => $keyStr === 'variance' && is_numeric($val) && (int) $val < 0,
                                                        'text-foreground' => $keyStr !== 'discount_amount' && ! ($keyStr === 'variance' && is_numeric($val) && (int) $val < 0),
                                                    ])>{{ $formatted }}</span>
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-muted-foreground">Belum ada catatan audit.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.rail>
