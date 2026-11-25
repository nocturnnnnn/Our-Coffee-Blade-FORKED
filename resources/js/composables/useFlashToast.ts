import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';
import { toast } from '@/composables/useToast';
import type { FlashToast } from '@/types/ui';

export function useFlashToast(): void {
    let removeListener: (() => void) | null = null;

    onMounted(() => {
        removeListener = router.on('flash', (event) => {
            const flash = (event as CustomEvent).detail?.flash;
            const data = flash?.toast as FlashToast | undefined;

            if (!data) {
                return;
            }

            toast[data.type](data.message);
        });
    });

    onUnmounted(() => {
        removeListener?.();
    });
}
