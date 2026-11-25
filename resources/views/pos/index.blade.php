<x-layouts.rail title="POS Kasir">
    <script type="application/json" id="pos-initial-data">
        {!! json_encode([
            'categories' => $categories,
            'pendingKioskOrders' => $pendingKioskOrders,
            'recentOrders' => $recentOrders,
            'cafeSettings' => $cafeSettings,
        ], JSON_HEX_TAG) !!}
    </script>

    <div x-data="posApp('pos-initial-data')" class="flex flex-col gap-5">
        {{-- Top Tabs --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="inline-flex rounded-xl border border-border bg-card p-1">
                <button
                    type="button"
                    @click="activeTab = 'walk_in'"
                    :class="activeTab === 'walk_in' ? 'bg-crema text-white shadow-xs' : 'text-muted-foreground hover:text-foreground'"
                    class="rounded-lg px-4 py-2 text-sm font-semibold transition"
                >
                    Kasir Walk-In
                </button>
                <button
                    type="button"
                    @click="activeTab = 'kiosk_queue'"
                    :class="activeTab === 'kiosk_queue' ? 'bg-crema text-white shadow-xs' : 'text-muted-foreground hover:text-foreground'"
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition"
                >
                    <span>Antrean Bayar Kiosk</span>
                    <span
                        x-show="pendingKioskOrders.length > 0"
                        class="rounded-full bg-amber-500 px-2 py-0.5 font-money text-xs font-bold text-espresso"
                        x-text="pendingKioskOrders.length"
                    ></span>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'recent_orders'"
                    :class="activeTab === 'recent_orders' ? 'bg-crema text-white shadow-xs' : 'text-muted-foreground hover:text-foreground'"
                    class="rounded-lg px-4 py-2 text-sm font-semibold transition"
                >
                    Riwayat Shift (<span x-text="recentOrders.length"></span>)
                </button>
            </div>

            <button
                type="button"
                @click="pollQueue()"
                class="inline-flex h-9 items-center gap-2 rounded-xl border border-border bg-card px-3.5 text-xs font-semibold hover:bg-muted"
            >
                <x-icon name="refresh-cw" class="size-3.5" />
                <span>Refresh Antrean</span>
            </button>
        </div>

        {{-- TAB 1: WALK-IN POS --}}
        <div x-show="activeTab === 'walk_in'" class="grid grid-cols-1 gap-5 lg:grid-cols-12">
            {{-- Left Catalog --}}
            <div class="space-y-4 lg:col-span-8">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative flex-1 min-w-[200px]">
                        <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <input
                            type="search"
                            x-model="searchQuery"
                            placeholder="Cari menu kopi, non-kopi, pastry..."
                            class="flex h-10 w-full rounded-xl border border-input bg-card pl-9 pr-3 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        />
                    </div>
                </div>

                <div class="flex gap-2 overflow-x-auto pb-1">
                    <button
                        type="button"
                        @click="activeCategoryId = 'all'"
                        :class="activeCategoryId === 'all' ? 'bg-crema text-white' : 'border border-border bg-card text-muted-foreground hover:text-foreground'"
                        class="whitespace-nowrap rounded-xl px-3.5 py-2 text-xs font-semibold transition"
                    >
                        Semua Menu
                    </button>
                    <template x-for="cat in categories" :key="cat.id">
                        <button
                            type="button"
                            @click="activeCategoryId = cat.id"
                            :class="activeCategoryId === cat.id ? 'bg-crema text-white' : 'border border-border bg-card text-muted-foreground hover:text-foreground'"
                            class="whitespace-nowrap rounded-xl px-3.5 py-2 text-xs font-semibold transition"
                            x-text="cat.name"
                        ></button>
                    </template>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                    <template x-for="item in filteredItems" :key="item.id">
                        <button
                            type="button"
                            @click="openItemSheet(item)"
                            :disabled="!item.is_available"
                            class="group relative flex flex-col overflow-hidden rounded-2xl border border-border bg-card text-left transition hover:border-amber-500/60 active:scale-[0.98] disabled:opacity-50"
                        >
                            <div class="relative aspect-[4/3] w-full bg-muted overflow-hidden">
                                <img :src="item.image_url" :alt="item.name" class="h-full w-full object-cover" />
                                <template x-if="!item.is_available">
                                    <div class="absolute inset-0 flex items-center justify-center bg-black/65">
                                        <span class="rounded bg-red-600 px-2 py-0.5 text-[10px] font-bold text-white uppercase">Habis</span>
                                    </div>
                                </template>
                            </div>
                            <div class="p-3">
                                <div class="text-xs font-bold line-clamp-2" x-text="item.name"></div>
                                <div class="mt-1 font-money text-xs font-bold text-amber-500" x-text="window.ocFormatRupiah(item.price)"></div>
                            </div>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Right Order Ticket --}}
            <div class="flex flex-col rounded-2xl border border-border bg-card p-4 lg:col-span-4 lg:sticky lg:top-6 lg:max-h-[calc(100vh-8rem)]">
                <div class="grid grid-cols-2 gap-2">
                    <button
                        type="button"
                        @click="orderType = 'dine_in'"
                        :class="orderType === 'dine_in' ? 'bg-crema text-white' : 'bg-muted text-muted-foreground'"
                        class="h-9 rounded-lg text-xs font-bold transition"
                    >
                        Dine-In
                    </button>
                    <button
                        type="button"
                        @click="orderType = 'takeaway'"
                        :class="orderType === 'takeaway' ? 'bg-crema text-white' : 'bg-muted text-muted-foreground'"
                        class="h-9 rounded-lg text-xs font-bold transition"
                    >
                        Takeaway
                    </button>
                </div>

                <input
                    type="text"
                    x-model="customerName"
                    placeholder="Nama pelanggan (opsional)"
                    class="mt-3 flex h-9 w-full rounded-lg border border-input bg-background px-3 text-xs"
                />

                <div class="my-3 flex-1 space-y-2.5 overflow-y-auto border-y border-border py-3">
                    <template x-if="cart.length === 0">
                        <div class="py-10 text-center text-xs text-muted-foreground">
                            Belum ada item di keranjang.
                        </div>
                    </template>

                    <template x-for="(item, idx) in cart" :key="item.signature">
                        <div class="rounded-xl border border-border bg-background/70 p-2.5">
                            <div class="flex items-start justify-between gap-2 text-xs">
                                <div>
                                    <div class="font-bold" x-text="item.name"></div>
                                    <template x-for="opt in item.selected_options" :key="opt.group_name + opt.option_name">
                                        <div class="text-[11px] text-muted-foreground" x-text="`+ ${opt.option_name}`"></div>
                                    </template>
                                    <template x-if="item.notes">
                                        <div class="text-[11px] italic text-amber-400" x-text="item.notes"></div>
                                    </template>
                                </div>
                                <div class="font-money font-bold" x-text="window.ocFormatRupiah(item.unitTotal * item.quantity)"></div>
                            </div>

                            <div class="mt-2 flex items-center justify-end gap-1.5">
                                <button type="button" @click="updateCartQty(idx, -1)" class="flex size-7 items-center justify-center rounded border border-border bg-card hover:bg-muted">
                                    <x-icon name="minus" class="size-3" />
                                </button>
                                <span class="w-6 text-center font-money text-xs font-bold" x-text="item.quantity"></span>
                                <button type="button" @click="updateCartQty(idx, 1)" class="flex size-7 items-center justify-center rounded border border-border bg-card hover:bg-muted">
                                    <x-icon name="plus" class="size-3" />
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Subtotal</span>
                        <span class="font-money font-semibold" x-text="window.ocFormatRupiah(cartSubtotal)"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <button type="button" @click="openDiscountModal()" class="inline-flex items-center gap-1 text-amber-400 hover:underline">
                            <x-icon name="percent" class="size-3.5" />
                            <span x-text="discountAmount > 0 ? 'Ubah Diskon' : 'Tambah Diskon (PIN)'"></span>
                        </button>
                        <div class="flex items-center gap-2">
                            <span class="font-money font-semibold text-red-400" x-show="discountAmount > 0" x-text="'-' + window.ocFormatRupiah(discountAmount)"></span>
                            <button type="button" x-show="discountAmount > 0" @click="clearDiscount()" class="text-muted-foreground hover:text-foreground">
                                <x-icon name="x" class="size-3" />
                            </button>
                        </div>
                    </div>
                    <div class="flex justify-between border-t border-border pt-2 text-base font-bold">
                        <span>Total</span>
                        <span class="font-money text-amber-500" x-text="window.ocFormatRupiah(Math.max(0, cartSubtotal - discountAmount))"></span>
                    </div>
                </div>

                <button
                    type="button"
                    @click="openWalkInPayment()"
                    :disabled="cart.length === 0"
                    class="mt-4 flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-crema text-sm font-bold text-white shadow-sm hover:bg-crema/90 disabled:opacity-40"
                >
                    <x-icon name="banknote" class="size-4" />
                    <span>Bayar & Proses</span>
                </button>
            </div>
        </div>

        {{-- TAB 2: KIOSK PENDING PAYMENT QUEUE --}}
        <div x-show="activeTab === 'kiosk_queue'" x-cloak class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <template x-if="pendingKioskOrders.length === 0">
                <div class="col-span-full rounded-2xl border border-border bg-card py-14 text-center text-sm text-muted-foreground">
                    Tidak ada pesanan Kiosk yang menunggu pembayaran di kasir.
                </div>
            </template>

            <template x-for="order in pendingKioskOrders" :key="order.id">
                <div class="flex flex-col justify-between rounded-2xl border border-amber-500/30 bg-card p-5">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="font-money text-xl font-extrabold text-amber-500" x-text="'#' + order.queue_number"></span>
                            <span class="rounded-lg bg-amber-500/15 px-2 py-0.5 text-xs font-semibold text-amber-400" x-text="order.order_type === 'dine_in' ? 'Dine-In' : 'Takeaway'"></span>
                        </div>
                        <div class="mt-1 text-xs text-muted-foreground">
                            <span class="font-money" x-text="order.order_number"></span> · <span x-text="order.customer_name || 'Tanpa Nama'"></span>
                        </div>
                        <div class="mt-3 space-y-1 border-y border-border py-3 text-xs">
                            <template x-for="item in order.items" :key="item.id">
                                <div class="flex justify-between">
                                    <span><strong x-text="item.quantity + 'x'"></strong> <span x-text="item.menu_item_name"></span></span>
                                    <span class="font-money" x-text="window.ocFormatRupiah(item.line_total)"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between gap-3">
                        <div class="font-money text-lg font-extrabold" x-text="window.ocFormatRupiah(order.total_amount)"></div>
                        <button
                            type="button"
                            @click="openKioskPayment(order)"
                            class="inline-flex h-10 items-center gap-1.5 rounded-xl bg-crema px-4 text-xs font-bold text-white hover:bg-crema/90"
                        >
                            <span>Terima Pembayaran</span>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        {{-- TAB 3: RECENT SHIFT ORDERS --}}
        <div x-show="activeTab === 'recent_orders'" x-cloak class="rounded-2xl border border-border bg-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-border bg-muted/50 text-xs text-muted-foreground uppercase">
                        <tr>
                            <th class="px-4 py-3">Order / Antrean</th>
                            <th class="px-4 py-3">Channel</th>
                            <th class="px-4 py-3">Pelanggan</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <template x-for="ord in recentOrders" :key="ord.id">
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-money font-bold" x-text="ord.order_number"></div>
                                    <div class="text-xs text-muted-foreground" x-text="'#' + ord.queue_number"></div>
                                </td>
                                <td class="px-4 py-3 text-xs uppercase" x-text="ord.channel"></td>
                                <td class="px-4 py-3 text-xs" x-text="ord.customer_name || '-'"></td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full px-2.5 py-0.5 text-xs font-semibold uppercase"
                                        :class="ord.status === 'voided' ? 'bg-red-500/15 text-red-400' : 'bg-emerald-500/15 text-emerald-400'"
                                        x-text="ord.status"
                                    ></span>
                                </td>
                                <td class="px-4 py-3 text-right font-money font-bold" x-text="window.ocFormatRupiah(ord.total_amount)"></td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex gap-1.5">
                                        <button
                                            type="button"
                                            @click="receiptOrder = ord"
                                            class="rounded-lg border border-border px-2.5 py-1 text-xs font-semibold hover:bg-muted"
                                        >
                                            Struk
                                        </button>
                                        <button
                                            type="button"
                                            x-show="ord.status !== 'voided' && ord.status !== 'cancelled'"
                                            @click="openVoidModal(ord)"
                                            class="rounded-lg border border-red-500/30 bg-red-500/10 px-2.5 py-1 text-xs font-semibold text-red-400 hover:bg-red-500/20"
                                        >
                                            Void
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- MODIFIER MODAL (WALK-IN) --}}
        <div x-show="modifierItem" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-xs">
            <div @click.outside="modifierItem = null" class="flex max-h-[88vh] w-full max-w-lg flex-col rounded-2xl border border-border bg-card shadow-2xl">
                <div class="flex items-center justify-between border-b border-border p-4">
                    <h3 class="text-base font-bold" x-text="modifierItem?.name"></h3>
                    <button type="button" @click="modifierItem = null" class="rounded-lg p-1.5 hover:bg-muted">
                        <x-icon name="x" class="size-4" />
                    </button>
                </div>
                <div class="flex-1 space-y-4 overflow-y-auto p-4">
                    <template x-for="group in (modifierItem?.modifier_groups || [])" :key="group.id">
                        <div class="space-y-2">
                            <div class="flex justify-between text-xs font-bold">
                                <span x-text="group.name"></span>
                                <span x-show="group.is_required" class="text-amber-400">Wajib</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <template x-for="opt in group.options" :key="opt.id">
                                    <button
                                        type="button"
                                        @click="toggleModifierOption(group, opt)"
                                        :class="isOptionSelected(group.id, opt.id) ? 'border-crema bg-crema/15 ring-1 ring-crema' : 'border-border bg-background'"
                                        class="flex items-center justify-between rounded-xl border px-3 py-2 text-xs"
                                    >
                                        <span class="font-medium" x-text="opt.name"></span>
                                        <span class="font-money text-amber-500" x-text="opt.price_delta > 0 ? '+' + window.ocFormatRupiah(opt.price_delta) : ''"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                    <div>
                        <label class="text-xs font-semibold">Catatan</label>
                        <input type="text" x-model="itemNotes" class="mt-1 flex h-9 w-full rounded-lg border border-input bg-background px-3 text-xs" />
                    </div>
                </div>
                <div class="flex items-center justify-between gap-3 border-t border-border p-4">
                    <div class="flex items-center gap-2">
                        <button type="button" @click="if (itemQty > 1) itemQty--" class="flex size-9 items-center justify-center rounded-lg border border-border">
                            <x-icon name="minus" class="size-4" />
                        </button>
                        <span class="w-6 text-center font-money font-bold" x-text="itemQty"></span>
                        <button type="button" @click="if (itemQty < 50) itemQty++" class="flex size-9 items-center justify-center rounded-lg border border-border">
                            <x-icon name="plus" class="size-4" />
                        </button>
                    </div>
                    <button
                        type="button"
                        @click="confirmModifierAdd()"
                        :disabled="!modifierSelectionValid"
                        class="h-10 flex-1 rounded-xl bg-crema px-4 text-sm font-bold text-white disabled:opacity-40"
                    >
                        Tambah · <span class="font-money" x-text="window.ocFormatRupiah(modifierUnitPrice * itemQty)"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- DISCOUNT MODAL --}}
        <div x-show="discountModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-xs">
            <div @click.outside="discountModalOpen = false" class="w-full max-w-sm rounded-2xl border border-border bg-card p-5 shadow-2xl space-y-4">
                <h3 class="text-base font-bold">Diskon Manual (Otorisasi PIN)</h3>
                <div class="grid gap-1.5">
                    <label class="text-xs font-medium">Nominal Diskon (Rp)</label>
                    <input type="number" min="0" x-model.number="tempDiscountAmount" class="h-10 rounded-lg border border-input bg-background px-3 font-money text-sm" />
                </div>
                <div class="grid gap-1.5">
                    <label class="text-xs font-medium">Alasan Diskon</label>
                    <input type="text" x-model="tempDiscountReason" placeholder="Contoh: Member / Kompensasi" class="h-10 rounded-lg border border-input bg-background px-3 text-sm" />
                </div>
                <div class="grid gap-1.5">
                    <label class="text-xs font-medium">PIN Admin (6 Digit)</label>
                    <input type="password" maxlength="6" x-model="tempDiscountPin" placeholder="••••••" class="h-10 rounded-lg border border-input bg-background px-3 font-money text-sm" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="discountModalOpen = false" class="h-9 rounded-lg border border-border px-4 text-xs font-medium">Batal</button>
                    <button type="button" @click="applyDiscountModal()" class="h-9 rounded-lg bg-crema px-4 text-xs font-bold text-white">Terapkan</button>
                </div>
            </div>
        </div>

        {{-- PAYMENT MODAL --}}
        <div x-show="paymentModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-xs">
            <div @click.outside="paymentModalOpen = false" class="w-full max-w-md rounded-2xl border border-border bg-card p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold" x-text="settlingOrder ? 'Bayar Pesanan Kiosk #' + settlingOrder.queue_number : 'Pembayaran POS'"></h3>
                    <button type="button" @click="paymentModalOpen = false" class="rounded-lg p-1.5 hover:bg-muted">
                        <x-icon name="x" class="size-4" />
                    </button>
                </div>

                <div class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-3.5 flex items-center justify-between">
                    <span class="text-xs font-semibold text-amber-400">Total Tagihan</span>
                    <span class="font-money text-xl font-extrabold text-amber-400" x-text="window.ocFormatRupiah(activePayTotal)"></span>
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <button type="button" @click="paymentMethod = 'cash'" :class="paymentMethod === 'cash' ? 'bg-crema text-white' : 'border border-border bg-background'" class="h-10 rounded-xl text-xs font-bold">Tunai</button>
                    <button type="button" @click="paymentMethod = 'edc'" :class="paymentMethod === 'edc' ? 'bg-crema text-white' : 'border border-border bg-background'" class="h-10 rounded-xl text-xs font-bold">Kartu EDC</button>
                    <button type="button" @click="paymentMethod = 'qris_manual'" :class="paymentMethod === 'qris_manual' ? 'bg-crema text-white' : 'border border-border bg-background'" class="h-10 rounded-xl text-xs font-bold">QRIS Statis</button>
                </div>

                <template x-if="paymentMethod === 'cash'">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between rounded-xl border border-input bg-background px-3.5 py-2.5">
                            <span class="text-xs text-muted-foreground">Tunai Diterima</span>
                            <span class="font-money text-lg font-extrabold" x-text="window.ocFormatRupiah(cashReceivedNum)"></span>
                        </div>

                        <div class="grid grid-cols-4 gap-1.5 text-xs">
                            <button type="button" @click="addCashPreset('EXACT')" class="rounded-lg border border-border bg-muted px-2 py-1.5 font-semibold hover:bg-accent">Uang Pas</button>
                            <button type="button" @click="addCashPreset(50000)" class="rounded-lg border border-border bg-muted px-2 py-1.5 font-money font-semibold hover:bg-accent">50rb</button>
                            <button type="button" @click="addCashPreset(100000)" class="rounded-lg border border-border bg-muted px-2 py-1.5 font-money font-semibold hover:bg-accent">100rb</button>
                            <button type="button" @click="addCashPreset(200000)" class="rounded-lg border border-border bg-muted px-2 py-1.5 font-money font-semibold hover:bg-accent">200rb</button>
                        </div>

                        @include('pos.partials.num-pad')

                        <div class="flex items-center justify-between text-sm font-bold">
                            <span>Kembalian</span>
                            <span class="font-money" :class="cashChange >= 0 ? 'text-emerald-400' : 'text-red-400'" x-text="window.ocFormatRupiah(Math.max(0, cashChange))"></span>
                        </div>
                    </div>
                </template>

                <p x-show="paymentError" x-text="paymentError" class="rounded-lg bg-red-500/15 p-2.5 text-xs text-red-400"></p>

                <button
                    type="button"
                    @click="submitPayment()"
                    :disabled="paymentBusy || (paymentMethod === 'cash' && cashChange < 0)"
                    class="flex h-12 w-full items-center justify-center rounded-xl bg-crema text-sm font-bold text-white hover:bg-crema/90 disabled:opacity-40"
                >
                    <span x-show="!paymentBusy">Selesaikan Pembayaran</span>
                    <span x-show="paymentBusy">Menyimpan...</span>
                </button>
            </div>
        </div>

        @include('pos.partials.supervisor-pin-modal')
        @include('pos.partials.thermal-receipt')
    </div>
</x-layouts.rail>
