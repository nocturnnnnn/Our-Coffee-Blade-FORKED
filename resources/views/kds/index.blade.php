<x-layouts.base title="Kitchen Display System (KDS)">
    <script type="application/json" id="kds-initial-data">
        {!! json_encode([
            'tickets' => $tickets,
            'serverTime' => $serverTime,
        ], JSON_HEX_TAG) !!}
    </script>

    <div x-data="kdsApp('kds-initial-data')" class="flex min-h-screen flex-col bg-background text-foreground">
        {{-- Top Bar --}}
        <header class="flex flex-wrap items-center justify-between gap-4 border-b border-border bg-card px-5 py-3.5">
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="flex size-10 items-center justify-center rounded-xl bg-crema text-white">
                    <x-icon name="chef-hat" class="size-5" />
                </a>
                <div>
                    <h1 class="text-lg font-extrabold tracking-tight">Kitchen Display System (KDS)</h1>
                    <p class="text-xs text-muted-foreground">Auto-sync setiap 4 detik · Urut antrean masuk</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <span class="rounded-xl border border-amber-500/30 bg-amber-500/10 px-3 py-1.5 font-money text-xs font-bold text-amber-400">
                    Antre: <span x-text="paidTickets.length"></span>
                </span>
                <span class="rounded-xl border border-sky-500/30 bg-sky-500/10 px-3 py-1.5 font-money text-xs font-bold text-sky-400">
                    Dibuat: <span x-text="preparingTickets.length"></span>
                </span>
                <span class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-3 py-1.5 font-money text-xs font-bold text-emerald-400">
                    Siap: <span x-text="readyTickets.length"></span>
                </span>

                <button
                    type="button"
                    @click="soundEnabled = !soundEnabled"
                    class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-border bg-background px-3 text-xs font-semibold hover:bg-muted"
                >
                    <span x-show="soundEnabled"><x-icon name="volume-2" class="size-4 text-emerald-400" /></span>
                    <span x-show="!soundEnabled" x-cloak><x-icon name="volume-x" class="size-4 text-muted-foreground" /></span>
                    <span x-text="soundEnabled ? 'Suara Aktif' : 'Suara Muted'"></span>
                </button>

                <button
                    type="button"
                    @click="fetchTickets()"
                    class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-border bg-background px-3 text-xs font-semibold hover:bg-muted"
                >
                    <x-icon name="refresh-cw" class="size-3.5" />
                    <span>Sync</span>
                </button>
            </div>
        </header>

        {{-- 3-Column Kitchen Board --}}
        <main class="grid flex-1 grid-cols-1 gap-4 p-4 lg:grid-cols-3 lg:p-6">
            {{-- Column 1: Antre / Masuk --}}
            <section class="flex flex-col rounded-2xl border border-border bg-card/50 p-4">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-extrabold tracking-wider text-amber-400 uppercase">Antre / Pesanan Masuk</h2>
                    <span class="rounded-full bg-amber-500/15 px-2.5 py-0.5 font-money text-xs font-bold text-amber-400" x-text="paidTickets.length"></span>
                </div>

                <div class="space-y-3">
                    <template x-if="paidTickets.length === 0">
                        <div class="rounded-xl border border-dashed border-border py-10 text-center text-xs text-muted-foreground">Tidak ada tiket antre</div>
                    </template>

                    <template x-for="ticket in paidTickets" :key="ticket.id">
                        <article
                            class="rounded-2xl border bg-card p-4 shadow-sm"
                            :class="elapsedMinutes(ticket) >= 8 ? 'border-red-500/60 ring-1 ring-red-500/30' : 'border-border'"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-money text-2xl font-extrabold text-amber-400" x-text="'#' + ticket.queue_number"></span>
                                        <span class="rounded-md bg-muted px-2 py-0.5 text-[10px] font-bold uppercase" x-text="ticket.order_type === 'dine_in' ? 'Dine-In' : 'Takeaway'"></span>
                                    </div>
                                    <div class="text-xs text-muted-foreground" x-text="ticket.customer_name || ticket.order_number"></div>
                                </div>
                                <span
                                    class="rounded-lg px-2 py-1 font-money text-xs font-bold"
                                    :class="elapsedMinutes(ticket) >= 8 ? 'bg-red-500/20 text-red-400' : 'bg-muted text-foreground'"
                                    x-text="elapsedLabel(ticket)"
                                ></span>
                            </div>

                            <div class="my-3 space-y-2 border-y border-border py-3 text-sm">
                                <template x-for="item in ticket.items" :key="item.id">
                                    <div>
                                        <div class="font-bold"><span class="font-money text-amber-400" x-text="item.quantity + 'x'"></span> <span x-text="item.menu_item_name"></span></div>
                                        <template x-for="mod in item.modifiers" :key="mod.group_name + mod.option_name">
                                            <div class="pl-4 text-xs text-muted-foreground" x-text="`• ${mod.option_name}`"></div>
                                        </template>
                                        <template x-if="item.notes">
                                            <div class="pl-4 text-xs font-medium text-amber-400" x-text="`* ${item.notes}`"></div>
                                        </template>
                                    </div>
                                </template>
                            </div>

                            <button
                                type="button"
                                @click="transition(ticket, 'preparing')"
                                :disabled="busyOrderId === ticket.id"
                                class="flex h-11 w-full items-center justify-center rounded-xl bg-crema text-sm font-bold text-white hover:bg-crema/90 disabled:opacity-50"
                            >
                                Mulai Buat
                            </button>
                        </article>
                    </template>
                </div>
            </section>

            {{-- Column 2: Sedang Dibuat --}}
            <section class="flex flex-col rounded-2xl border border-border bg-card/50 p-4">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-extrabold tracking-wider text-sky-400 uppercase">Sedang Dibuat</h2>
                    <span class="rounded-full bg-sky-500/15 px-2.5 py-0.5 font-money text-xs font-bold text-sky-400" x-text="preparingTickets.length"></span>
                </div>

                <div class="space-y-3">
                    <template x-if="preparingTickets.length === 0">
                        <div class="rounded-xl border border-dashed border-border py-10 text-center text-xs text-muted-foreground">Belum ada pesanan diracik</div>
                    </template>

                    <template x-for="ticket in preparingTickets" :key="ticket.id">
                        <article class="rounded-2xl border border-sky-500/35 bg-card p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-money text-2xl font-extrabold text-sky-400" x-text="'#' + ticket.queue_number"></span>
                                        <span class="rounded-md bg-muted px-2 py-0.5 text-[10px] font-bold uppercase" x-text="ticket.order_type === 'dine_in' ? 'Dine-In' : 'Takeaway'"></span>
                                    </div>
                                    <div class="text-xs text-muted-foreground" x-text="ticket.customer_name || ticket.order_number"></div>
                                </div>
                                <span class="rounded-lg bg-sky-500/15 px-2 py-1 font-money text-xs font-bold text-sky-400" x-text="elapsedLabel(ticket)"></span>
                            </div>

                            <div class="my-3 space-y-2 border-y border-border py-3 text-sm">
                                <template x-for="item in ticket.items" :key="item.id">
                                    <div>
                                        <div class="font-bold"><span class="font-money text-sky-400" x-text="item.quantity + 'x'"></span> <span x-text="item.menu_item_name"></span></div>
                                        <template x-for="mod in item.modifiers" :key="mod.group_name + mod.option_name">
                                            <div class="pl-4 text-xs text-muted-foreground" x-text="`• ${mod.option_name}`"></div>
                                        </template>
                                        <template x-if="item.notes">
                                            <div class="pl-4 text-xs font-medium text-amber-400" x-text="`* ${item.notes}`"></div>
                                        </template>
                                    </div>
                                </template>
                            </div>

                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="transition(ticket, 'paid')"
                                    :disabled="busyOrderId === ticket.id"
                                    title="Kembalikan ke Antre"
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-border bg-muted hover:bg-accent disabled:opacity-50"
                                >
                                    <x-icon name="arrow-left" class="size-4" />
                                </button>
                                <button
                                    type="button"
                                    @click="transition(ticket, 'ready')"
                                    :disabled="busyOrderId === ticket.id"
                                    class="flex h-11 flex-1 items-center justify-center rounded-xl bg-emerald-600 text-sm font-bold text-white hover:bg-emerald-500 disabled:opacity-50"
                                >
                                    Tandai Siap Diambil
                                </button>
                            </div>
                        </article>
                    </template>
                </div>
            </section>

            {{-- Column 3: Siap Diambil --}}
            <section class="flex flex-col rounded-2xl border border-border bg-card/50 p-4">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-extrabold tracking-wider text-emerald-400 uppercase">Siap Diambil</h2>
                    <span class="rounded-full bg-emerald-500/15 px-2.5 py-0.5 font-money text-xs font-bold text-emerald-400" x-text="readyTickets.length"></span>
                </div>

                <div class="space-y-3">
                    <template x-if="readyTickets.length === 0">
                        <div class="rounded-xl border border-dashed border-border py-10 text-center text-xs text-muted-foreground">Belum ada pesanan siap</div>
                    </template>

                    <template x-for="ticket in readyTickets" :key="ticket.id">
                        <article class="rounded-2xl border border-emerald-500/40 bg-card p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-money text-2xl font-extrabold text-emerald-400" x-text="'#' + ticket.queue_number"></span>
                                        <span class="rounded-md bg-muted px-2 py-0.5 text-[10px] font-bold uppercase" x-text="ticket.order_type === 'dine_in' ? 'Dine-In' : 'Takeaway'"></span>
                                    </div>
                                    <div class="text-xs text-muted-foreground" x-text="ticket.customer_name || ticket.order_number"></div>
                                </div>
                                <span class="rounded-lg bg-emerald-500/15 px-2 py-1 text-xs font-bold text-emerald-400">READY</span>
                            </div>

                            <div class="my-3 space-y-1 border-y border-border py-3 text-xs text-muted-foreground">
                                <template x-for="item in ticket.items" :key="item.id">
                                    <div><strong x-text="item.quantity + 'x'"></strong> <span x-text="item.menu_item_name"></span></div>
                                </template>
                            </div>

                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="transition(ticket, 'preparing')"
                                    :disabled="busyOrderId === ticket.id"
                                    title="Kembalikan ke Sedang Dibuat"
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-border bg-muted hover:bg-accent disabled:opacity-50"
                                >
                                    <x-icon name="arrow-left" class="size-4" />
                                </button>
                                <button
                                    type="button"
                                    @click="transition(ticket, 'completed')"
                                    :disabled="busyOrderId === ticket.id"
                                    class="flex h-11 flex-1 items-center justify-center rounded-xl border border-border bg-muted text-sm font-bold hover:bg-accent disabled:opacity-50"
                                >
                                    Sudah Diserahkan
                                </button>
                            </div>
                        </article>
                    </template>
                </div>
            </section>
        </main>
    </div>
</x-layouts.base>
