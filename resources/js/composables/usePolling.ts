import { onMounted, onUnmounted, watch, type Ref } from 'vue';

export function usePolling(
    callback: () => void | Promise<void>,
    intervalMs: number,
    enabled: boolean | Ref<boolean> = true,
): void {
    let timerId: number | null = null;

    const isEnabled = () =>
        typeof enabled === 'boolean' ? enabled : enabled.value;

    const tick = () => {
        if (document.visibilityState === 'visible' && isEnabled()) {
            void callback();
        }
    };

    const startTimer = () => {
        if (timerId !== null) {
            window.clearInterval(timerId);
        }
        if (isEnabled()) {
            timerId = window.setInterval(tick, intervalMs);
        }
    };

    const stopTimer = () => {
        if (timerId !== null) {
            window.clearInterval(timerId);
            timerId = null;
        }
    };

    const handleVisibilityChange = () => {
        if (document.visibilityState === 'visible' && isEnabled()) {
            void callback();
            startTimer();
        } else {
            stopTimer();
        }
    };

    onMounted(() => {
        if (document.visibilityState === 'visible' && isEnabled()) {
            startTimer();
        }
        document.addEventListener('visibilitychange', handleVisibilityChange);
    });

    onUnmounted(() => {
        stopTimer();
        document.removeEventListener('visibilitychange', handleVisibilityChange);
    });

    if (typeof enabled !== 'boolean') {
        watch(enabled, (val) => {
            if (val && document.visibilityState === 'visible') {
                startTimer();
            } else {
                stopTimer();
            }
        });
    }
}
