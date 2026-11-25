import { reactive } from 'vue';

export type KioskStep =
    | 'welcome'
    | 'order_type'
    | 'menu'
    | 'cart'
    | 'payment'
    | 'confirmation';

export type OrderTypeValue = 'dine_in' | 'takeaway';

export type KioskModifierOption = {
    id: number;
    name: string;
    price_delta: number;
    is_default: boolean;
};

export type KioskModifierGroup = {
    id: number;
    name: string;
    selection_type: 'single' | 'multiple';
    is_required: boolean;
    min_select: number;
    max_select: number;
    options: KioskModifierOption[];
};

export type KioskMenuItem = {
    id: number;
    category_id: number;
    name: string;
    description: string | null;
    price: number;
    image_url: string | null;
    badge: 'best_seller' | 'new' | null;
    is_available: boolean;
    unavailable_reason: string | null;
    max_servings: number | null;
    modifier_groups: KioskModifierGroup[];
};

export type KioskCategory = {
    id: number;
    name: string;
    icon: string | null;
    items: KioskMenuItem[];
};

export type KioskCartItem = {
    lineId: string;
    menuItemId: number;
    name: string;
    imageUrl: string | null;
    unitPrice: number;
    modifiersTotal: number;
    quantity: number;
    notes: string;
    selectedModifiers: Array<{
        optionId: number;
        groupName: string;
        optionName: string;
        priceDelta: number;
    }>;
};

function makeSignature(
    menuItemId: number,
    selectedModifiers: KioskCartItem['selectedModifiers'],
    notes: string,
): string {
    const modKey = selectedModifiers
        .map((m) => m.optionId)
        .sort((a, b) => a - b)
        .join(',');
    return `${menuItemId}|${modKey}|${notes.trim()}`;
}

export const kioskCartStore = reactive({
    step: 'welcome' as KioskStep,
    orderType: null as OrderTypeValue | null,
    customerName: '',
    items: [] as KioskCartItem[],

    setStep(step: KioskStep) {
        this.step = step;
    },

    setOrderType(orderType: OrderTypeValue) {
        this.orderType = orderType;
    },

    setCustomerName(name: string) {
        this.customerName = name;
    },

    addOrUpdateItem(
        incoming: Omit<KioskCartItem, 'lineId'> & { lineId?: string },
    ) {
        if (incoming.lineId) {
            this.items = this.items.map((item) =>
                item.lineId === incoming.lineId
                    ? { ...incoming, lineId: incoming.lineId! }
                    : item,
            );
            return;
        }

        const sig = makeSignature(
            incoming.menuItemId,
            incoming.selectedModifiers,
            incoming.notes,
        );

        const existing = this.items.find(
            (item) =>
                makeSignature(
                    item.menuItemId,
                    item.selectedModifiers,
                    item.notes,
                ) === sig,
        );

        if (existing) {
            existing.quantity += incoming.quantity;
            return;
        }

        const lineId = `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
        this.items.push({ ...incoming, lineId });
    },

    updateQuantity(lineId: string, delta: number) {
        this.items = this.items
            .map((item) =>
                item.lineId === lineId
                    ? { ...item, quantity: item.quantity + delta }
                    : item,
            )
            .filter((item) => item.quantity > 0);
    },

    removeItem(lineId: string) {
        this.items = this.items.filter((item) => item.lineId !== lineId);
    },

    removeItemsByMenuIds(menuIds: number[]) {
        this.items = this.items.filter(
            (item) => !menuIds.includes(item.menuItemId),
        );
    },

    clearCart() {
        this.items = [];
    },

    resetSession() {
        this.step = 'welcome';
        this.orderType = null;
        this.customerName = '';
        this.items = [];
    },
});
