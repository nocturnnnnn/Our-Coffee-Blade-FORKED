<x-layouts.rail title="Dashboard Operasional">
    <script type="application/json" id="admin-dashboard-data">
        {!! json_encode([
            'days' => $days,
            'kpis' => $kpis,
            'revenueTrend' => $revenueTrend,
            'channelSplit' => $channelSplit,
            'peakHours' => $peakHours,
            'topMenus' => $topMenus,
            'lowStockIngredients' => $lowStockIngredients,
        ], JSON_HEX_TAG) !!}
    </script>

    <div x-data="adminDashboardApp('admin-dashboard-data')" class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Dashboard Operasional</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">Ringkasan KPI real-time (auto-refresh setiap 30 detik).</p>
            </div>
            <div class="inline-flex rounded-xl border border-border bg-card p-1">
                <button
                    type="button"
                    @click="switchDays(7)"
                    :class="days === 7 ? 'bg-amber-500 text-espresso font-bold shadow-xs' : 'text-muted-foreground hover:text-foreground'"
                    class="rounded-lg px-3.5 py-1.5 text-xs transition"
                >
                    7 Hari
                </button>
                <button
                    type="button"
                    @click="switchDays(30)"
                    :class="days === 30 ? 'bg-amber-500 text-espresso font-bold shadow-xs' : 'text-muted-foreground hover:text-foreground'"
                    class="rounded-lg px-3.5 py-1.5 text-xs transition"
                >
                    30 Hari
                </button>
            </div>
        </div>

        {{-- 4 KPI Cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-border bg-card p-5">
                <div class="flex items-center justify-between text-xs text-muted-foreground">
                    <span>Omzet Hari Ini</span>
                    <x-icon name="wallet" class="size-4 text-amber-500" />
                </div>
                <div class="mt-2 font-money text-2xl font-extrabold text-amber-500" x-text="window.ocFormatRupiah(kpis.today_revenue)"></div>
            </div>

            <div class="rounded-2xl border border-border bg-card p-5">
                <div class="flex items-center justify-between text-xs text-muted-foreground">
                    <span>Total Transaksi</span>
                    <x-icon name="receipt" class="size-4 text-amber-500" />
                </div>
                <div class="mt-2 font-money text-2xl font-extrabold" x-text="kpis.today_orders"></div>
            </div>

            <div class="rounded-2xl border border-border bg-card p-5">
                <div class="flex items-center justify-between text-xs text-muted-foreground">
                    <span>Average Order Value</span>
                    <x-icon name="trending-up" class="size-4 text-emerald-500" />
                </div>
                <div class="mt-2 font-money text-2xl font-extrabold" x-text="window.ocFormatRupiah(kpis.today_aov)"></div>
            </div>

            <div class="rounded-2xl border border-border bg-card p-5">
                <div class="flex items-center justify-between text-xs text-muted-foreground">
                    <span>Bahan Stok Menipis</span>
                    <span :class="kpis.low_stock_count > 0 ? 'text-red-500' : 'text-emerald-500'">
                        <x-icon name="alert-triangle" class="size-4" />
                    </span>
                </div>
                <div
                    class="mt-2 font-money text-2xl font-extrabold"
                    :class="kpis.low_stock_count > 0 ? 'text-red-400' : 'text-foreground'"
                    x-text="kpis.low_stock_count"
                ></div>
            </div>
        </div>

        {{-- Trend & Channel Split --}}
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <div class="rounded-2xl border border-border bg-card p-5 lg:col-span-2">
                <h2 class="text-sm font-bold" x-text="`Tren Omzet (${days} Hari Terakhir)`"></h2>

                <div class="mt-5 flex h-52 items-end gap-2">
                    <template x-for="point in revenueTrend" :key="point.date">
                        <div class="group flex flex-1 flex-col items-center gap-2 h-full justify-end">
                            <div
                                class="w-full rounded-t-lg bg-crema transition-all group-hover:bg-amber-500"
                                :style="`height: ${Math.max(4, Math.round((Number(point.revenue || 0) / maxTrendRevenue) * 100))}%`"
                                :title="`${point.label}: ${window.ocFormatRupiah(point.revenue)} (${point.orders} order)`"
                            ></div>
                            <span class="text-[10px] text-muted-foreground font-money" x-text="point.label"></span>
                        </div>
                    </template>
                </div>
            </div>

            <div class="rounded-2xl border border-border bg-card p-5 flex flex-col justify-between">
                <h2 class="text-sm font-bold">Kontribusi Channel</h2>

                <div class="my-4 flex items-center justify-center">
                    <svg viewBox="0 0 132 132" class="size-36 -rotate-90">
                        <circle
                            cx="66"
                            cy="66"
                            r="52"
                            fill="transparent"
                            stroke="currentColor"
                            class="text-muted/70"
                            stroke-width="18"
                        />
                        <circle
                            cx="66"
                            cy="66"
                            r="52"
                            fill="transparent"
                            stroke="#b45309"
                            stroke-width="18"
                            :stroke-dasharray="donutCircumference"
                            :stroke-dashoffset="kioskDashOffset"
                            stroke-linecap="butt"
                        />
                    </svg>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-2">
                            <span class="size-2.5 rounded-full bg-crema"></span>
                            <span>Self-Ordering Kiosk (<span x-text="kioskPercent"></span>%)</span>
                        </span>
                        <span class="font-money font-semibold" x-text="window.ocFormatRupiah(channelSplit.kiosk_revenue)"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-2">
                            <span class="size-2.5 rounded-full bg-muted-foreground/40"></span>
                            <span>POS Kasir (<span x-text="posPercent"></span>%)</span>
                        </span>
                        <span class="font-money font-semibold" x-text="window.ocFormatRupiah(channelSplit.pos_revenue)"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Peak Hours Heatmap --}}
        <div class="rounded-2xl border border-border bg-card p-5">
            <h2 class="text-sm font-bold">Heatmap Jam Sibuk (Peak Hours)</h2>
            <div class="mt-4 grid grid-cols-4 gap-2 sm:grid-cols-6 md:grid-cols-12">
                <template x-for="slot in peakHours" :key="slot.hour">
                    <div
                        class="rounded-xl border border-border p-2.5 text-center transition"
                        :class="slot.orders === 0
                            ? 'bg-muted/30 text-muted-foreground'
                            : (slot.orders >= maxPeakOrders * 0.7
                                ? 'border-amber-500/60 bg-crema text-white'
                                : 'border-amber-500/30 bg-crema/35 text-foreground')"
                    >
                        <div class="font-money text-[11px]" x-text="slot.label"></div>
                        <div class="mt-0.5 font-money text-sm font-bold" x-text="slot.orders"></div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Bottom Grid: Top 5 Menus + Low Stock Warning --}}
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <div class="rounded-2xl border border-border bg-card p-5 lg:col-span-2">
                <div class="mb-4 flex items-center gap-2">
                    <x-icon name="shopping-bag" class="size-4 text-amber-500" />
                    <h2 class="text-sm font-bold">Top 5 Menu & Margin</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-border text-muted-foreground uppercase">
                            <tr>
                                <th class="pb-2.5">Menu</th>
                                <th class="pb-2.5">Terjual</th>
                                <th class="pb-2.5">Omzet</th>
                                <th class="pb-2.5 text-right">Margin</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <template x-if="topMenus.length === 0">
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-muted-foreground">Belum ada data penjualan pada rentang ini.</td>
                                </tr>
                            </template>
                            <template x-for="row in topMenus" :key="row.menu_item_id">
                                <tr>
                                    <td class="py-3 font-semibold" x-text="row.name"></td>
                                    <td class="py-3 font-money" x-text="row.qty_sold"></td>
                                    <td class="py-3 font-money" x-text="window.ocFormatRupiah(row.revenue)"></td>
                                    <td class="py-3 text-right font-money font-bold text-emerald-400" x-text="row.margin_percent + '%'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl border border-border bg-card p-5 flex flex-col">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-bold">Peringatan Stok Menipis</h2>
                    <a href="{{ route('admin.inventory.index') }}" class="text-xs font-semibold text-amber-500 hover:underline">Kelola Stok</a>
                </div>

                <div class="flex-1 space-y-2.5">
                    <template x-if="lowStockIngredients.length === 0">
                        <div class="flex h-36 items-center justify-center text-center text-xs text-muted-foreground">
                            Seluruh stok bahan baku dalam batas aman.
                        </div>
                    </template>
                    <template x-for="ing in lowStockIngredients" :key="ing.id">
                        <div class="flex items-center justify-between rounded-xl border border-red-500/30 bg-red-500/10 px-3.5 py-2.5 text-xs">
                            <div>
                                <div class="font-semibold text-foreground" x-text="ing.name"></div>
                                <div class="text-[11px] text-muted-foreground">Min: <span class="font-money" x-text="`${ing.min_stock} ${ing.unit}`"></span></div>
                            </div>
                            <span class="font-money font-bold text-red-400" x-text="`${ing.current_stock} ${ing.unit}`"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</x-layouts.rail>
