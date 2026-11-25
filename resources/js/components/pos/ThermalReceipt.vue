<script setup lang="ts">
import { Printer } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import Button from '@/components/ui/Button.vue';
import Dialog from '@/components/ui/Dialog.vue';
import { formatDateWib, formatRupiah, formatTimeWib } from '@/lib/format';
import { cn } from '@/lib/utils';

export type ReceiptOrder = {
    id: number;
    order_number: string;
    queue_number: string;
    channel: string;
    order_type: string;
    customer_name: string | null;
    status: string;
    subtotal: number;
    discount_amount: number;
    discount_reason: string | null;
    total_amount: number;
    cashier_name: string | null;
    paid_at: string | null;
    created_at: string | null;
    payment: {
        method: string;
        method_label: string;
        status: string;
        amount: number;
        cash_received: number | null;
        change_amount: number | null;
    } | null;
    items: Array<{
        id: number;
        menu_item_id: number;
        menu_item_name: string;
        unit_price: number;
        modifiers_total: number;
        quantity: number;
        line_total: number;
        notes: string | null;
        modifiers: Array<{
            group_name: string;
            option_name: string;
            price_delta: number;
        }>;
    }>;
};

const props = defineProps<{
    order: ReceiptOrder | null;
    cafeSettings: {
        cafe_name: string;
        cafe_address: string;
        cafe_phone: string;
        receipt_footer: string;
    };
    open: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const paperWidth = ref<'58mm' | '80mm'>('80mm');
const timestamp = computed(() => props.order?.paid_at ?? props.order?.created_at);

function handlePrint() {
    window.print();
}
</script>

<template>
    <Dialog
        v-if="order"
        :open="open"
        @close="emit('close')"
        class="max-h-[90vh] overflow-y-auto sm:max-w-md"
    >
        <div class="mb-4">
            <h2 class="text-lg font-bold">Struk Pembayaran Thermal</h2>
        </div>

        <div class="flex items-center justify-center gap-2">
            <Button
                size="sm"
                :variant="paperWidth === '58mm' ? 'default' : 'outline'"
                @click="paperWidth = '58mm'"
                class="min-h-10"
            >
                58mm
            </Button>
            <Button
                size="sm"
                :variant="paperWidth === '80mm' ? 'default' : 'outline'"
                @click="paperWidth = '80mm'"
                class="min-h-10"
            >
                80mm
            </Button>
        </div>

        <div class="flex justify-center overflow-x-auto py-2">
            <div
                :class="
                    cn(
                        'thermal-receipt-print rounded-lg border bg-white p-4 font-mono text-xs text-black shadow-xs',
                        paperWidth === '58mm' ? 'w-[220px]' : 'w-[300px]',
                    )
                "
            >
                <div class="border-b border-dashed border-black/40 pb-2 text-center">
                    <p class="text-sm font-bold uppercase">
                        {{ cafeSettings.cafe_name }}
                    </p>
                    <p v-if="cafeSettings.cafe_address" class="mt-0.5 text-[10px]">
                        {{ cafeSettings.cafe_address }}
                    </p>
                    <p v-if="cafeSettings.cafe_phone" class="text-[10px]">
                        {{ cafeSettings.cafe_phone }}
                    </p>
                </div>

                <div class="space-y-0.5 border-b border-dashed border-black/40 py-2 text-[11px]">
                    <div class="flex justify-between">
                        <span>Antrean</span>
                        <span class="font-bold">#{{ order.queue_number }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Order</span>
                        <span>{{ order.order_number }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Tipe</span>
                        <span class="uppercase">
                            {{ order.order_type === 'dine_in' ? 'Dine-In' : 'Takeaway' }}
                        </span>
                    </div>
                    <div v-if="order.customer_name" class="flex justify-between">
                        <span>Pelanggan</span>
                        <span>{{ order.customer_name }}</span>
                    </div>
                    <div v-if="order.cashier_name" class="flex justify-between">
                        <span>Kasir</span>
                        <span>{{ order.cashier_name }}</span>
                    </div>
                    <div v-if="timestamp" class="flex justify-between">
                        <span>Waktu</span>
                        <span>{{ formatDateWib(timestamp) }} {{ formatTimeWib(timestamp) }}</span>
                    </div>
                </div>

                <div class="space-y-2 border-b border-dashed border-black/40 py-2">
                    <div v-for="item in order.items" :key="item.id" class="space-y-0.5">
                        <div class="flex justify-between font-semibold">
                            <span>{{ item.quantity }}x {{ item.menu_item_name }}</span>
                            <span>{{ formatRupiah(item.line_total) }}</span>
                        </div>
                        <p v-if="item.modifiers.length > 0" class="pl-3 text-[10px] text-black/75">
                            {{ item.modifiers.map((m) => m.option_name).join(', ') }}
                        </p>
                        <p v-if="item.notes" class="pl-3 text-[10px] italic">
                            * {{ item.notes }}
                        </p>
                    </div>
                </div>

                <div class="space-y-1 border-b border-dashed border-black/40 py-2 text-[11px]">
                    <div class="flex justify-between">
                        <span>Subtotal</span>
                        <span>{{ formatRupiah(order.subtotal) }}</span>
                    </div>
                    <div v-if="order.discount_amount > 0" class="flex justify-between">
                        <span>Diskon</span>
                        <span>-{{ formatRupiah(order.discount_amount) }}</span>
                    </div>
                    <div class="flex justify-between text-sm font-bold">
                        <span>TOTAL</span>
                        <span>{{ formatRupiah(order.total_amount) }}</span>
                    </div>
                    <template v-if="order.payment">
                        <div class="flex justify-between pt-1">
                            <span>Bayar ({{ order.payment.method_label }})</span>
                            <span>
                                {{
                                    formatRupiah(
                                        order.payment.cash_received ?? order.payment.amount,
                                    )
                                }}
                            </span>
                        </div>
                        <div v-if="order.payment.change_amount !== null" class="flex justify-between">
                            <span>Kembalian</span>
                            <span>{{ formatRupiah(order.payment.change_amount) }}</span>
                        </div>
                    </template>
                </div>

                <p class="pt-3 text-center text-[10px]">
                    {{ cafeSettings.receipt_footer }}
                </p>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <Button variant="outline" @click="emit('close')" class="min-h-12">
                Tutup
            </Button>
            <Button @click="handlePrint" class="min-h-12 gap-2 font-bold">
                <Printer class="size-4" />
                Cetak Struk ({{ paperWidth }})
            </Button>
        </div>
    </Dialog>
</template>
