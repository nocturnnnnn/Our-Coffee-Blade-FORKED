<x-layouts.base title="Layar Antrean Pesanan">
    <script type="application/json" id="queue-display-data">
        {!! json_encode([
            'cafeName' => $cafeName,
            'preparing' => $preparing,
            'ready' => $ready,
        ], JSON_HEX_TAG) !!}
    </script>

    <div x-data="queueDisplayApp('queue-display-data')" class="flex min-h-screen flex-col bg-[#150e0a] text-[#fdfbf7] select-none">
        <header class="flex items-center justify-between border-b border-[#382216] bg-[#1c120d] px-6 py-4">
            <div class="flex items-center gap-3.5">
                <div class="flex size-11 items-center justify-center rounded-xl bg-crema text-white">
                    <x-icon name="coffee" class="size-6" />
                </div>
                <div>
                    <h1 class="text-xl font-extrabold tracking-tight" x-text="cafeName"></h1>
                    <p class="text-xs text-[#d4c2b2]">Status Antrean Pesanan</p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <span class="font-money text-lg font-bold text-amber-400" x-text="clockText"></span>
                <button
                    type="button"
                    @click="soundEnabled = !soundEnabled"
                    class="rounded-xl border border-[#4a3225] bg-[#221610] p-2.5 text-[#fdfbf7] hover:bg-[#352217]"
                    title="Toggle Suara Panggilan"
                >
                    <span x-show="soundEnabled"><x-icon name="volume-2" class="size-5 text-emerald-400" /></span>
                    <span x-show="!soundEnabled" x-cloak><x-icon name="volume-x" class="size-5 text-[#d4c2b2]" /></span>
                </button>
            </div>
        </header>

        <main class="grid flex-1 grid-cols-1 gap-6 p-6 md:grid-cols-2">
            {{-- Preparing --}}
            <section class="flex flex-col rounded-3xl border border-[#4a3225] bg-[#221610] p-6">
                <div class="mb-5 flex items-center justify-between border-b border-[#4a3225] pb-4">
                    <h2 class="text-2xl font-extrabold tracking-wide text-amber-400 uppercase">Sedang Disiapkan</h2>
                    <span class="rounded-full bg-amber-500/20 px-3.5 py-1 font-money text-lg font-bold text-amber-400" x-text="preparing.length"></span>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <template x-if="preparing.length === 0">
                        <div class="col-span-full py-16 text-center text-sm text-[#d4c2b2]/70">Belum ada antrean diproses</div>
                    </template>

                    <template x-for="ord in preparing" :key="ord.id">
                        <div class="rounded-2xl border border-[#4a3225] bg-[#150e0a] p-4 text-center">
                            <div class="font-money text-3xl font-extrabold text-amber-400" x-text="'#' + ord.queue_number"></div>
                            <div class="mt-1 truncate text-xs text-[#d4c2b2]" x-text="ord.customer_name || (ord.order_type === 'dine_in' ? 'Dine-In' : 'Takeaway')"></div>
                        </div>
                    </template>
                </div>
            </section>

            {{-- Ready --}}
            <section class="flex flex-col rounded-3xl border border-emerald-500/40 bg-[#182219] p-6">
                <div class="mb-5 flex items-center justify-between border-b border-emerald-500/30 pb-4">
                    <h2 class="text-2xl font-extrabold tracking-wide text-emerald-400 uppercase">Siap Diambil</h2>
                    <span class="rounded-full bg-emerald-500/20 px-3.5 py-1 font-money text-lg font-bold text-emerald-400" x-text="ready.length"></span>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <template x-if="ready.length === 0">
                        <div class="col-span-full py-16 text-center text-sm text-emerald-200/60">Pesanan yang sudah selesai akan muncul di sini</div>
                    </template>

                    <template x-for="ord in ready" :key="ord.id">
                        <div class="rounded-2xl border-2 border-emerald-400/70 bg-emerald-950/60 p-6 text-center shadow-lg">
                            <div class="font-money text-5xl font-extrabold text-emerald-300" x-text="'#' + ord.queue_number"></div>
                            <div class="mt-2 truncate text-base font-bold text-white" x-text="ord.customer_name || (ord.order_type === 'dine_in' ? 'Dine-In' : 'Takeaway')"></div>
                        </div>
                    </template>
                </div>
            </section>
        </main>
    </div>
</x-layouts.base>
