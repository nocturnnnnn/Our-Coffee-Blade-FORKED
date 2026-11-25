<div
    x-show="receiptOrder"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-xs print:static print:bg-transparent print:p-0"
>
    <div class="flex max-h-[92vh] w-full max-w-md flex-col rounded-2xl border border-border bg-card shadow-2xl print:max-h-none print:border-none print:shadow-none">
        <div class="flex items-center justify-between border-b border-border px-5 py-3.5 print:hidden">
            <div class="flex items-center gap-2">
                <span class="text-sm font-bold">Struk Pembayaran</span>
                <div class="inline-flex rounded-lg border border-border bg-muted p-0.5 text-xs">
                    <button
                        type="button"
                        @click="receiptPaper = '58mm'"
                        :class="receiptPaper === '58mm' ? 'bg-card font-semibold shadow-xs' : 'text-muted-foreground'"
                        class="rounded px-2 py-0.5"
                    >58mm</button>
                    <button
                        type="button"
                        @click="receiptPaper = '80mm'"
                        :class="receiptPaper === '80mm' ? 'bg-card font-semibold shadow-xs' : 'text-muted-foreground'"
                        class="rounded px-2 py-0.5"
                    >80mm</button>
                </div>
            </div>
            <button type="button" @click="receiptOrder = null" class="rounded-lg p-1.5 hover:bg-muted">
                <x-icon name="x" class="size-4" />
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-5 flex justify-center print:overflow-visible print:p-0">
            <div
                class="thermal-receipt-print bg-white text-black p-4 rounded-lg shadow-sm font-mono text-xs leading-relaxed"
                :class="receiptPaper === '58mm' ? 'w-[58mm] text-[10px]' : 'w-[80mm] text-xs'"
            >
                <div class="text-center border-b border-dashed border-neutral-400 pb-2 mb-2">
                    <div class="text-sm font-bold uppercase" x-text="cafeSettings.cafe_name || 'Our Coffee'"></div>
                    <template x-if="cafeSettings.cafe_address">
                        <div class="text-[10px]" x-text="cafeSettings.cafe_address"></div>
                    </template>
                    <template x-if="cafeSettings.cafe_phone">
                        <div class="text-[10px]" x-text="'Telp: ' + cafeSettings.cafe_phone"></div>
                    </template>
                </div>

                <div class="space-y-0.5 border-b border-dashed border-neutral-400 pb-2 mb-2">
                    <div class="flex justify-between">
                        <span>No. Order</span>
                        <span class="font-bold" x-text="receiptOrder?.order_number"></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Antrean</span>
                        <span class="font-bold text-sm" x-text="'#' + (receiptOrder?.queue_number || '')"></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Waktu</span>
                        <span x-text="window.ocFormatDateTimeWib(receiptOrder?.paid_at || receiptOrder?.created_at)"></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Kasir</span>
                        <span x-text="receiptOrder?.cashier_name || '-'"></span>
                    </div>
                    <template x-if="receiptOrder?.customer_name">
                        <div class="flex justify-between">
                            <span>Pelanggan</span>
                            <span x-text="receiptOrder?.customer_name"></span>
                        </div>
                    </template>
                </div>

                <div class="space-y-1.5 border-b border-dashed border-neutral-400 pb-2 mb-2">
                    <template x-for="item in (receiptOrder?.items || [])" :key="item.id">
                        <div>
                            <div class="flex justify-between font-bold">
                                <span x-text="`${item.quantity}x ${item.menu_item_name}`"></span>
                                <span x-text="window.ocFormatRupiah(item.line_total)"></span>
                            </div>
                            <template x-for="mod in (item.modifiers || [])" :key="mod.group_name + mod.option_name">
                                <div class="pl-3 text-[10px] text-neutral-600" x-text="`+ ${mod.option_name}`"></div>
                            </template>
                            <template x-if="item.notes">
                                <div class="pl-3 text-[10px] italic text-neutral-600" x-text="`* ${item.notes}`"></div>
                            </template>
                        </div>
                    </template>
                </div>

                <div class="space-y-1 border-b border-dashed border-neutral-400 pb-2 mb-2">
                    <div class="flex justify-between">
                        <span>Subtotal</span>
                        <span x-text="window.ocFormatRupiah(receiptOrder?.subtotal)"></span>
                    </div>
                    <template x-if="(receiptOrder?.discount_amount || 0) > 0">
                        <div class="flex justify-between">
                            <span>Diskon</span>
                            <span x-text="'-' + window.ocFormatRupiah(receiptOrder?.discount_amount)"></span>
                        </div>
                    </template>
                    <div class="flex justify-between text-sm font-bold">
                        <span>TOTAL</span>
                        <span x-text="window.ocFormatRupiah(receiptOrder?.total_amount)"></span>
                    </div>
                    <template x-if="receiptOrder?.payment">
                        <div class="pt-1 space-y-0.5 text-[11px]">
                            <div class="flex justify-between">
                                <span x-text="'Bayar (' + receiptOrder.payment.method_label + ')'"></span>
                                <span x-text="window.ocFormatRupiah(receiptOrder.payment.cash_received || receiptOrder.payment.amount)"></span>
                            </div>
                            <template x-if="receiptOrder.payment.change_amount !== null && receiptOrder.payment.change_amount > 0">
                                <div class="flex justify-between font-bold">
                                    <span>Kembalian</span>
                                    <span x-text="window.ocFormatRupiah(receiptOrder.payment.change_amount)"></span>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <div class="text-center text-[10px] pt-1" x-text="cafeSettings.receipt_footer || 'Terima kasih telah berkunjung!'"></div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 border-t border-border p-4 print:hidden">
            <button type="button" @click="receiptOrder = null" class="h-10 rounded-xl border border-border px-4 text-sm font-medium hover:bg-muted">
                Tutup
            </button>
            <button type="button" @click="printReceipt()" class="inline-flex h-10 items-center gap-2 rounded-xl bg-crema px-5 text-sm font-bold text-white hover:bg-crema/90">
                <x-icon name="printer" class="size-4" />
                <span>Cetak Struk</span>
            </button>
        </div>
    </div>
</div>
