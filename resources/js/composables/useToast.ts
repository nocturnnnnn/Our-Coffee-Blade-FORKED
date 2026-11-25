import { reactive } from 'vue';

export type ToastItem = {
    id: number;
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
    actionLabel?: string;
    onAction?: () => void;
};

let nextId = 1;

export const toastState = reactive<{ items: ToastItem[] }>({
    items: [],
});

function addToast(
    type: ToastItem['type'],
    message: string,
    options?: {
        duration?: number;
        action?: { label: string; onClick: () => void };
    },
): number {
    const id = nextId++;
    toastState.items.push({
        id,
        type,
        message,
        actionLabel: options?.action?.label,
        onAction: options?.action?.onClick,
    });

    const duration = options?.duration ?? 4000;
    if (duration > 0) {
        window.setTimeout(() => {
            dismissToast(id);
        }, duration);
    }

    return id;
}

export function dismissToast(id: number): void {
    const idx = toastState.items.findIndex((t) => t.id === id);
    if (idx !== -1) {
        toastState.items.splice(idx, 1);
    }
}

export const toast = {
    success: (
        message: string,
        options?: {
            duration?: number;
            action?: { label: string; onClick: () => void };
        },
    ) => addToast('success', message, options),
    info: (
        message: string,
        options?: {
            duration?: number;
            action?: { label: string; onClick: () => void };
        },
    ) => addToast('info', message, options),
    warning: (
        message: string,
        options?: {
            duration?: number;
            action?: { label: string; onClick: () => void };
        },
    ) => addToast('warning', message, options),
    error: (
        message: string,
        options?: {
            duration?: number;
            action?: { label: string; onClick: () => void };
        },
    ) => addToast('error', message, options),
};
