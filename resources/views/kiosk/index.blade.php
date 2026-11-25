<x-layouts.base title="Self-Ordering Kiosk">
    <script type="application/json" id="kiosk-initial-data">
        {!! json_encode([
            'device' => $device,
            'isLocalDemo' => $isLocalDemo,
            'settings' => $settings,
            'categories' => $categories,
        ], JSON_HEX_TAG) !!}
    </script>

    <div
        x-data="kioskApp('kiosk-initial-data')"
        class="relative flex min-h-screen flex-col bg-background text-foreground select-none"
    >
        {{-- 1. WELCOME SCREEN --}}
        <template x-if="screen === 'welcome'">
            <div class="relative flex min-h-screen flex-col items-center justify-between p-6 md:p-10">
                <div class="flex w-full max-w-6xl items-center justify-between text-xs text-muted-foreground">
                    <button
                        type="button"
                        @click="registerTopLeftTap()"
                        class="rounded-lg px-2 py-1 text-left opacity-70 hover:opacity-100"
                        title="Ketuk 5x untuk keluar mode Kiosk"
                    >
                        <span x-text="device.name || 'Kiosk'"></span>
                    </button>
                    <span>Self-Ordering Kiosk</span>
                </div>

                <div class="flex max-w-md flex-col items-center text-center">
                    <div class="mb-5 flex size-20 items-center justify-center rounded-2xl bg-crema text-white shadow-xl">
                        <x-icon name="coffee" class="size-10" />
                    </div>
                    <h1 class="text-4xl font-extrabold tracking-tight md:text-5xl" x-text="settings.cafe_name"></h1>
                    <p class="mt-3 text-base text-muted-foreground">
                        Pesan kopi favorit Anda dengan cepat tanpa antre di kasir.
                    </p>
                    <button
                        type="button"
                        @click="startOrdering()"
                        class="mt-8 inline-flex min-h-14 items-center justify-center rounded-2xl bg-crema px-10 py-4 text-lg font-bold text-white shadow-lg transition hover:bg-crema/90 active:scale-95"
                    >
                        Mulai Pesan
                    </button>
                </div>

                <p class="text-xs text-muted-foreground/80">
                    Ketuk tombol di atas untuk memulai pesanan Anda
                </p>
            </div>
        </template>

        {{-- 2. ORDER TYPE SCREEN --}}
        <template x-if="screen === 'order_type'">
            <div class="flex min-h-screen flex-col items-center justify-center p-6">
                <div class="w-full max-w-2xl text-center">
                    <h2 class="text-3xl font-extrabold tracking-tight">Mau nikmati di mana?</h2>
                    <p class="mt-2 text-sm text-muted-foreground">Pilih tipe pesanan Anda terlebih dahulu</p>

                    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <button
                            type="button"
                            @click="chooseOrderType('dine_in')"
                            class="group flex flex-col items-center justify-center rounded-2xl border border-border bg-card p-8 text-center transition hover:border-amber-500/60 hover:bg-card/80 active:scale-[0.98]"
                        >
                            <div class="mb-4 flex size-14 items-center justify-center rounded-2xl bg-muted text-foreground group-hover:bg-crema group-hover:text-white transition">
                                <x-icon name="store" class="size-7" />
                            </div>
                            <div class="text-xl font-bold">Dine-In</div>
                            <div class="mt-1 text-xs text-muted-foreground">Minum di tempat</div>
                        </button>

                        <button
                            type="button"
                            @click="chooseOrderType('takeaway')"
                            class="group flex flex-col items-center justify-center rounded-2xl border border-border bg-card p-8 text-center transition hover:border-amber-500/60 hover:bg-card/80 active:scale-[0.98]"
                        >
                            <div class="mb-4 flex size-14 items-center justify-center rounded-2xl bg-muted text-foreground group-hover:bg-crema group-hover:text-white transition">
                                <x-icon name="shopping-bag" class="size-7" />
                            </div>
                            <div class="text-xl font-bold">Takeaway</div>
                            <div class="mt-1 text-xs text-muted-foreground">Bawa pulang</div>
                        </button>
                    </div>

                    <div class="mx-auto mt-6 max-w-sm rounded-2xl border border-border bg-card p-4 text-left">
                        <label for="kiosk_customer_name" class="block text-xs font-semibold text-foreground">
                            Nama Panggilan untuk Antrean (Opsional)
                        </label>
                        <input
                            id="kiosk_customer_name"
                            type="text"
                            x-model="customerName"
                            maxlength="80"
                            placeholder="Contoh: Rafi"
                            class="mt-2 flex h-11 w-full rounded-xl border border-input bg-background px-3.5 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        />
                    </div>

                    <button
                        type="button"
                        @click="screen = 'welcome'"
                        class="mt-6 inline-flex h-11 items-center justify-center rounded-xl border border-border bg-card px-6 text-sm font-semibold hover:bg-muted"
                    >
                        Kembali
                    </button>
                </div>
            </div>
        </template>

        {{-- 3. MENU & CART SCREEN --}}
        <template x-if="screen === 'menu'">
            <div class="flex min-h-screen flex-col">
                {{-- Top Header --}}
                <header class="sticky top-0 z-30 flex items-center justify-between border-b border-border bg-card/95 px-4 py-3 backdrop-blur-md">
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            @click="screen = 'order_type'"
                            class="flex size-10 items-center justify-center rounded-xl border border-border bg-background hover:bg-muted"
                        >
                            <x-icon name="arrow-left" class="size-5" />
                        </button>
                        <div>
                            <div class="text-sm font-bold" x-text="settings.cafe_name"></div>
                            <div class="text-xs text-muted-foreground">
                                <span x-text="orderType === 'dine_in' ? 'Dine-In' : 'Takeaway'"></span>
                                <template x-if="customerName.trim()">
                                    <span> · <span x-text="customerName.trim()"></span></span>
                                </template>
                            </div>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="cartOpen = true"
                        class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-sm font-bold text-espresso shadow-sm transition hover:bg-amber-400 active:scale-95"
                    >
                        <x-icon name="shopping-bag" class="size-4" />
                        <span>Keranjang (<span x-text="cartCount"></span>)</span>
                        <span class="font-money" x-text="window.ocFormatRupiah(cartSubtotal)"></span>
                    </button>
                </header>

                {{-- Body: Category Rail + Menu Grid --}}
                <div class="flex flex-1 overflow-hidden">
                    <aside class="w-24 shrink-0 space-y-2 overflow-y-auto border-r border-border bg-card/40 p-2 sm:w-32 sm:p-3">
                        <template x-for="cat in categories" :key="cat.id">
                            <button
                                type="button"
                                @click="activeCategoryId = cat.id"
                                :class="activeCategoryId === cat.id
                                    ? 'bg-crema text-white shadow-sm'
                                    : 'bg-transparent text-muted-foreground hover:bg-muted hover:text-foreground'"
                                class="flex w-full flex-col items-center justify-center gap-1.5 rounded-xl px-2 py-3 text-center text-xs font-semibold transition"
                            >
                                <x-icon name="coffee" class="size-5 shrink-0" />
                                <span class="leading-tight" x-text="cat.name"></span>
                            </button>
                        </template>
                    </aside>

                    <main class="flex-1 overflow-y-auto p-4 md:p-6">
                        <template x-if="activeCategory">
                            <div>
                                <h2 class="mb-4 text-xl font-bold" x-text="activeCategory.name"></h2>
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    <template x-for="item in activeCategory.items" :key="item.id">
                                        <button
                                            type="button"
                                            @click="openItemSheet(item)"
                                            :disabled="!item.is_available"
                                            class="group relative flex flex-col overflow-hidden rounded-2xl border border-border bg-card text-left transition hover:border-amber-500/50 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-55"
                                        >
                                            <div class="relative aspect-[4/3] w-full overflow-hidden bg-muted">
                                                <img
                                                    :src="item.image_url"
                                                    :alt="item.name"
                                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                                />
                                                <template x-if="item.badge">
                                                    <span
                                                        class="absolute top-2.5 left-2.5 rounded-lg px-2 py-0.5 text-[11px] font-bold shadow-xs"
                                                        :class="item.badge === 'best_seller' ? 'bg-crema text-white' : 'bg-espresso/85 text-white'"
                                                        x-text="item.badge === 'best_seller' ? 'Best Seller' : 'New'"
                                                    ></span>
                                                </template>
                                                <template x-if="!item.is_available">
                                                    <div class="absolute inset-0 flex items-center justify-center bg-black/65 backdrop-blur-[1px]">
                                                        <span class="rounded-lg bg-red-600 px-3 py-1 text-xs font-bold text-white uppercase">
                                                            Habis
                                                        </span>
                                                    </div>
                                                </template>
                                            </div>
                                            <div class="flex flex-1 flex-col justify-between p-4">
                                                <div>
                                                    <h3 class="text-base font-bold text-foreground" x-text="item.name"></h3>
                                                    <p class="mt-1 line-clamp-2 text-xs text-muted-foreground" x-text="item.description"></p>
                                                </div>
                                                <div class="mt-3 flex items-center justify-between">
                                                    <span class="font-money text-sm font-bold text-amber-500" x-text="window.ocFormatRupiah(item.price)"></span>
                                                    <span x-show="!item.is_available && item.unavailable_reason" class="text-[11px] text-red-400" x-text="item.unavailable_reason"></span>
                                                </div>
                                            </div>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </main>
                </div>
            </div>
        </template>

        {{-- 4. CONFIRMATION SCREEN --}}
        <template x-if="screen === 'confirmation'">
            <div class="flex min-h-screen flex-col items-center justify-center p-6 text-center">
                <div class="w-full max-w-md rounded-3xl border border-border bg-card p-8 shadow-2xl">
                    <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-2xl bg-emerald-500/15 text-emerald-500">
                        <x-icon name="check-circle-2" class="size-10" />
                    </div>
                    <h2 class="text-2xl font-extrabold">Pesanan Diterima!</h2>
                    <p class="mt-1 text-sm text-muted-foreground" x-text="confirmationMethod === 'qris'
                        ? 'Pembayaran QRIS berhasil dikonfirmasi. Pesanan sedang diteruskan ke barista.'
                        : 'Silakan lakukan pembayaran di kasir sambil menyebutkan nomor antrean Anda.'"></p>

                    <div class="my-6 rounded-2xl border border-amber-500/30 bg-amber-500/10 p-5">
                        <div class="text-xs font-semibold tracking-wider text-amber-400 uppercase">Nomor Antrean</div>
                        <div class="mt-1 font-money text-5xl font-extrabold text-amber-500" x-text="currentOrder?.queue_number"></div>
                        <div class="mt-2 font-money text-xs text-muted-foreground" x-text="currentOrder?.order_number"></div>
                    </div>

                    <p class="text-xs text-muted-foreground">
                        Layar akan kembali otomatis dalam <span class="font-money font-bold text-foreground" x-text="confirmationCountdown"></span> detik
                    </p>
                    <button
                        type="button"
                        @click="resetSession()"
                        class="mt-5 inline-flex h-12 w-full items-center justify-center rounded-xl bg-crema px-6 text-sm font-bold text-white hover:bg-crema/90"
                    >
                        Pesan Lagi Sekarang
                    </button>
                </div>
            </div>
        </template>

        {{-- CART DRAWER --}}
        <div
            x-show="cartOpen"
            x-cloak
            class="fixed inset-0 z-40 flex justify-end bg-black/70 backdrop-blur-xs"
        >
            <div @click.outside="cartOpen = false" class="flex h-full w-full max-w-md flex-col border-l border-border bg-card shadow-2xl">
                <div class="flex items-center justify-between border-b border-border p-5">
                    <div>
                        <h3 class="text-lg font-bold">Keranjang Pesanan</h3>
                        <p class="text-xs text-muted-foreground" x-text="orderType === 'dine_in' ? 'Dine-In' : 'Takeaway'"></p>
                    </div>
                    <button type="button" @click="cartOpen = false" class="rounded-full p-2 hover:bg-muted">
                        <x-icon name="x" class="size-5" />
                    </button>
                </div>

                <div class="flex-1 space-y-3 overflow-y-auto p-5">
                    <template x-if="cart.length === 0">
                        <div class="py-12 text-center text-sm text-muted-foreground">
                            Keranjang masih kosong. Silakan pilih menu terlebih dahulu.
                        </div>
                    </template>

                    <template x-for="(item, index) in cart" :key="item.signature">
                        <div class="rounded-2xl border border-border bg-background/70 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-semibold" x-text="item.name"></div>
                                    <div class="mt-1 space-y-0.5 text-xs text-muted-foreground">
                                        <template x-for="opt in item.selected_options" :key="opt.group_name + opt.option_name">
                                            <div>• <span x-text="opt.group_name"></span>: <span x-text="opt.option_name"></span></div>
                                        </template>
                                        <template x-if="item.notes">
                                            <div class="italic text-amber-400">Catatan: <span x-text="item.notes"></span></div>
                                        </template>
                                    </div>
                                </div>
                                <div class="font-money text-sm font-bold text-amber-500" x-text="window.ocFormatRupiah(item.unitTotal * item.quantity)"></div>
                            </div>

                            <div class="mt-3 flex items-center justify-end gap-2">
                                <button type="button" @click="updateCartQty(index, -1)" class="flex size-9 items-center justify-center rounded-lg border border-border bg-card hover:bg-muted">
                                    <x-icon name="minus" class="size-4" />
                                </button>
                                <span class="w-8 text-center font-money text-sm font-bold" x-text="item.quantity"></span>
                                <button type="button" @click="updateCartQty(index, 1)" class="flex size-9 items-center justify-center rounded-lg border border-border bg-card hover:bg-muted">
                                    <x-icon name="plus" class="size-4" />
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="border-t border-border p-5 space-y-4">
                    <div class="flex items-center justify-between text-base font-bold">
                        <span>Total Tagihan</span>
                        <span class="font-money text-xl text-amber-500" x-text="window.ocFormatRupiah(cartSubtotal)"></span>
                    </div>
                    <button
                        type="button"
                        @click="openPaymentModal()"
                        :disabled="cart.length === 0"
                        class="flex h-13 w-full items-center justify-center rounded-xl bg-crema text-base font-bold text-white shadow-sm hover:bg-crema/90 disabled:opacity-40"
                    >
                        Lanjut Pembayaran
                    </button>
                </div>
            </div>
        </div>

        {{-- PAYMENT METHOD & QRIS MODAL --}}
        <div
            x-show="paymentModalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-xs"
        >
            <div class="w-full max-w-md rounded-3xl border border-border bg-card p-6 shadow-2xl">
                <template x-if="paymentMode === 'select'">
                    <div>
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-bold">Pilih Pembayaran</h3>
                            <button type="button" @click="paymentModalOpen = false" class="rounded-full p-2 hover:bg-muted">
                                <x-icon name="x" class="size-5" />
                            </button>
                        </div>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Total tagihan: <span class="font-money font-bold text-amber-500" x-text="window.ocFormatRupiah(cartSubtotal)"></span>
                        </p>

                        <p x-show="checkoutError" x-text="checkoutError" class="mt-3 rounded-lg bg-red-500/15 p-2.5 text-xs text-red-400"></p>

                        <div class="mt-5 space-y-3">
                            <button
                                type="button"
                                @click="submitCheckout('qris')"
                                :disabled="checkoutBusy"
                                class="flex w-full items-center justify-between rounded-2xl border border-border bg-background p-4 text-left transition hover:border-amber-500 disabled:opacity-50"
                            >
                                <div>
                                    <div class="font-bold">QRIS Instant</div>
                                    <div class="text-xs text-muted-foreground">Scan QR dengan GoPay, OVO, Dana, ShopeePay, BCA</div>
                                </div>
                                <x-icon name="arrow-right" class="size-5 text-amber-500" />
                            </button>

                            <button
                                type="button"
                                @click="submitCheckout('cashier')"
                                :disabled="checkoutBusy"
                                class="flex w-full items-center justify-between rounded-2xl border border-border bg-background p-4 text-left transition hover:border-amber-500 disabled:opacity-50"
                            >
                                <div>
                                    <div class="font-bold">Bayar di Kasir</div>
                                    <div class="text-xs text-muted-foreground">Bayar tunai atau kartu debit/kredit di meja kasir</div>
                                </div>
                                <x-icon name="banknote" class="size-5 text-amber-500" />
                            </button>
                        </div>
                    </div>
                </template>

                <template x-if="paymentMode === 'qris'">
                    <div class="text-center">
                        <h3 class="text-xl font-bold">Scan QRIS untuk Membayar</h3>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Pesanan <span class="font-money font-semibold" x-text="currentOrder?.order_number"></span>
                        </p>

                        <div class="my-5" x-html="qrisSvg"></div>

                        <div class="font-money text-2xl font-extrabold text-amber-500" x-text="window.ocFormatRupiah(currentOrder?.total_amount)"></div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Berlaku dalam <span class="font-money font-bold text-foreground" x-text="qrisCountdown"></span> detik
                        </p>

                        <p x-show="checkoutError" x-text="checkoutError" class="mt-3 rounded-lg bg-red-500/15 p-2 text-xs text-red-400"></p>

                        <div class="mt-5 space-y-2.5">
                            <template x-if="isLocalDemo">
                                <button
                                    type="button"
                                    @click="simulateQrisPaid()"
                                    :disabled="simulatingPaid"
                                    class="flex h-11 w-full items-center justify-center rounded-xl bg-emerald-600 text-sm font-bold text-white hover:bg-emerald-500 disabled:opacity-50"
                                >
                                    <span x-show="!simulatingPaid">Simulasi Bayar Lunas (Demo)</span>
                                    <span x-show="simulatingPaid">Memproses...</span>
                                </button>
                            </template>

                            <template x-if="qrisCountdown === 0">
                                <button
                                    type="button"
                                    @click="regenerateQris()"
                                    class="flex h-11 w-full items-center justify-center rounded-xl bg-crema text-sm font-bold text-white hover:bg-crema/90"
                                >
                                    Buat Ulang QRIS
                                </button>
                            </template>

                            <button
                                type="button"
                                @click="resetSession()"
                                class="flex h-10 w-full items-center justify-center rounded-xl border border-border text-xs font-semibold text-muted-foreground hover:bg-muted"
                            >
                                Batalkan Pesanan
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- IDLE TIMEOUT WARNING MODAL --}}
        <div
            x-show="idleWarningOpen"
            x-cloak
            class="fixed inset-0 z-[65] flex items-center justify-center bg-black/80 p-4 backdrop-blur-xs"
        >
            <div class="w-full max-w-sm rounded-3xl border border-amber-500/40 bg-card p-6 text-center shadow-2xl">
                <x-icon name="clock" class="mx-auto size-10 text-amber-500" />
                <h3 class="mt-3 text-xl font-bold">Masih di sana?</h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    Sesi Kiosk akan direset otomatis dalam
                    <span class="font-money font-bold text-foreground" x-text="Math.max(0, settings.idle_timeout_seconds - idleSeconds)"></span> detik.
                </p>
                <div class="mt-5 flex gap-3">
                    <button
                        type="button"
                        @click="resetSession()"
                        class="flex h-11 flex-1 items-center justify-center rounded-xl border border-border text-sm font-semibold hover:bg-muted"
                    >
                        Reset
                    </button>
                    <button
                        type="button"
                        @click="continueAfterIdle()"
                        class="flex h-11 flex-1 items-center justify-center rounded-xl bg-crema text-sm font-bold text-white hover:bg-crema/90"
                    >
                        Lanjut Pesan
                    </button>
                </div>
            </div>
        </div>

        @include('kiosk.partials.modifier-sheet')
        @include('kiosk.partials.pin-exit-modal')
    </div>
</x-layouts.base>
