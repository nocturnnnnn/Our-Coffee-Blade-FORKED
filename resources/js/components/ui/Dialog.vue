<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue';
import { X } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        open: boolean;
        class?: string;
        showClose?: boolean;
    }>(),
    {
        showClose: true,
    },
);

const emit = defineEmits<{
    (e: 'close'): void;
}>();

function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape' && props.open) {
        emit('close');
    }
}

onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleKeydown));
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-[1px]"
            @mousedown.self="emit('close')"
        >
            <div
                role="dialog"
                aria-modal="true"
                :class="
                    cn(
                        'relative w-full max-w-lg rounded-xl border border-border bg-background p-6 text-foreground shadow-xl',
                        props.class,
                    )
                "
            >
                <button
                    v-if="showClose"
                    type="button"
                    @click="emit('close')"
                    class="absolute top-4 right-4 rounded-md p-1 text-muted-foreground opacity-70 transition-opacity hover:opacity-100"
                    aria-label="Tutup"
                >
                    <X class="size-4" />
                </button>
                <slot />
            </div>
        </div>
    </Teleport>
</template>
