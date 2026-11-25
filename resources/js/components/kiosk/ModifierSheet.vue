<script setup lang="ts">
import { Minus, Plus } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import { formatRupiah } from '@/lib/format';
import { cn } from '@/lib/utils';
import type {
    KioskCartItem,
    KioskMenuItem,
    KioskModifierGroup,
} from '@/stores/kiosk-cart-store';

const props = defineProps<{
    menuItem: KioskMenuItem | null;
    editingCartItem?: KioskCartItem | null;
    open: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (
        e: 'confirm',
        item: Omit<KioskCartItem, 'lineId'> & { lineId?: string },
    ): void;
}>();

const selections = ref<Record<number, number[]>>({});
const quantity = ref(1);
const notes = ref('');

function buildInitialSelections(
    menuItem: KioskMenuItem,
    editingCartItem?: KioskCartItem | null,
): Record<number, number[]> {
    const initial: Record<number, number[]> = {};
    const editingIds = new Set(
        editingCartItem?.selectedModifiers.map((m) => m.optionId) ?? [],
    );

    for (const group of menuItem.modifier_groups) {
        if (editingCartItem) {
            initial[group.id] = group.options
                .filter((opt) => editingIds.has(opt.id))
                .map((opt) => opt.id);
        } else {
            const defaults = group.options
                .filter((opt) => opt.is_default)
                .map((opt) => opt.id);

            if (
                defaults.length === 0 &&
                group.is_required &&
                group.options[0]
            ) {
                initial[group.id] = [group.options[0].id];
            } else {
                initial[group.id] = defaults;
            }
        }
    }

    return initial;
}

watch(
    () => [props.open, props.menuItem?.id, props.editingCartItem?.lineId],
    () => {
        if (props.open && props.menuItem) {
            selections.value = buildInitialSelections(
                props.menuItem,
                props.editingCartItem,
            );
            quantity.value = props.editingCartItem?.quantity ?? 1;
            notes.value = props.editingCartItem?.notes ?? '';
        }
    },
    { immediate: true },
);

function toggleOption(group: KioskModifierGroup, optionId: number) {
    const current = selections.value[group.id] ?? [];

    if (group.selection_type === 'single') {
        if (current.includes(optionId)) {
            if (group.is_required || group.min_select >= 1) {
                return;
            }
            selections.value = { ...selections.value, [group.id]: [] };
            return;
        }
        selections.value = { ...selections.value, [group.id]: [optionId] };
        return;
    }

    if (current.includes(optionId)) {
        if (current.length <= group.min_select) {
            return;
        }
        selections.value = {
            ...selections.value,
            [group.id]: current.filter((id) => id !== optionId),
        };
        return;
    }

    if (current.length >= group.max_select) {
        return;
    }

    selections.value = {
        ...selections.value,
        [group.id]: [...current, optionId],
    };
}

const computedSummary = computed(() => {
    const selectedModifiers: KioskCartItem['selectedModifiers'] = [];
    let modifiersTotal = 0;

    if (!props.menuItem) {
        return { selectedModifiers, modifiersTotal };
    }

    for (const group of props.menuItem.modifier_groups) {
        const chosenIds = selections.value[group.id] ?? [];
        for (const opt of group.options) {
            if (chosenIds.includes(opt.id)) {
                modifiersTotal += opt.price_delta;
                selectedModifiers.push({
                    optionId: opt.id,
                    groupName: group.name,
                    optionName: opt.name,
                    priceDelta: opt.price_delta,
                });
            }
        }
    }

    return { selectedModifiers, modifiersTotal };
});

const isValidSelection = computed(() => {
    if (!props.menuItem) {
        return false;
    }

    return props.menuItem.modifier_groups.every((group) => {
        const count = (selections.value[group.id] ?? []).length;
        const min = group.is_required
            ? Math.max(1, group.min_select)
            : group.min_select;
        return count >= min && count <= group.max_select;
    });
});

const lineTotal = computed(() => {
    if (!props.menuItem) {
        return 0;
    }
    return (
        (props.menuItem.price + computedSummary.value.modifiersTotal) *
        quantity.value
    );
});

function handleConfirm() {
    if (!props.menuItem || !isValidSelection.value) {
        return;
    }
    emit('confirm', {
        lineId: props.editingCartItem?.lineId,
        menuItemId: props.menuItem.id,
        name: props.menuItem.name,
        imageUrl: props.menuItem.image_url,
        unitPrice: props.menuItem.price,
        modifiersTotal: computedSummary.value.modifiersTotal,
        quantity: quantity.value,
        notes: notes.value.trim(),
        selectedModifiers: computedSummary.value.selectedModifiers,
    });
    emit('close');
}
</script>

<template>
    <Dialog
        v-if="menuItem"
        :open="open"
        @close="emit('close')"
        class="max-h-[90vh] overflow-y-auto sm:max-w-xl"
    >
        <div class="mb-4">
            <h2 class="text-xl font-bold">{{ menuItem.name }}</h2>
            <p v-if="menuItem.description" class="text-sm text-muted-foreground">
                {{ menuItem.description }}
            </p>
        </div>

        <div class="space-y-5 py-2">
            <div
                v-for="group in menuItem.modifier_groups"
                :key="group.id"
                class="space-y-2.5"
            >
                <div class="flex items-center justify-between">
                    <p class="text-sm font-bold">{{ group.name }}</p>
                    <Badge :variant="group.is_required ? 'default' : 'secondary'">
                        {{
                            group.is_required
                                ? 'Wajib'
                                : group.selection_type === 'multiple'
                                  ? `Opsional · Maks ${group.max_select}`
                                  : 'Opsional'
                        }}
                    </Badge>
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    <button
                        v-for="opt in group.options"
                        :key="opt.id"
                        type="button"
                        @click="toggleOption(group, opt.id)"
                        :class="
                            cn(
                                'flex min-h-16 flex-col items-start justify-center rounded-xl border-2 px-4 py-2.5 text-left transition-colors',
                                (selections[group.id] ?? []).includes(opt.id)
                                    ? 'border-primary bg-primary/10 text-foreground'
                                    : 'border-border bg-card hover:bg-muted/50',
                            )
                        "
                    >
                        <span class="text-sm font-semibold">{{ opt.name }}</span>
                        <span class="font-money text-xs text-muted-foreground">
                            {{
                                opt.price_delta > 0
                                    ? `+${formatRupiah(opt.price_delta)}`
                                    : 'Gratis'
                            }}
                        </span>
                    </button>
                </div>
            </div>

            <div class="space-y-1.5">
                <Label for="kiosk-item-notes">Catatan Khusus (Opsional)</Label>
                <Input
                    id="kiosk-item-notes"
                    v-model="notes"
                    maxlength="255"
                    placeholder="Contoh: Es sedikit, pisah gelas"
                    class="h-12"
                />
            </div>

            <div class="flex items-center justify-between rounded-2xl bg-muted/60 p-3">
                <span class="text-sm font-semibold">Jumlah</span>
                <div class="flex items-center gap-3">
                    <Button
                        variant="outline"
                        @click="quantity = Math.max(1, quantity - 1)"
                        class="min-h-16 min-w-16 rounded-xl"
                        aria-label="Kurangi jumlah"
                    >
                        <Minus class="size-6" />
                    </Button>
                    <span class="font-money min-w-10 text-center text-xl font-bold">
                        {{ quantity }}
                    </span>
                    <Button
                        variant="outline"
                        @click="quantity = Math.min(50, quantity + 1)"
                        class="min-h-16 min-w-16 rounded-xl"
                        aria-label="Tambah jumlah"
                    >
                        <Plus class="size-6" />
                    </Button>
                </div>
            </div>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
            <Button
                variant="outline"
                @click="emit('close')"
                class="min-h-16 px-6 text-base"
            >
                Batal
            </Button>
            <Button
                :disabled="!isValidSelection"
                @click="handleConfirm"
                class="min-h-16 flex-1 px-6 text-base font-bold"
            >
                <span>
                    {{ editingCartItem ? 'Simpan Perubahan' : 'Tambah ke Keranjang' }}
                </span>
                <span class="font-money ml-2">· {{ formatRupiah(lineTotal) }}</span>
            </Button>
        </div>
    </Dialog>
</template>
