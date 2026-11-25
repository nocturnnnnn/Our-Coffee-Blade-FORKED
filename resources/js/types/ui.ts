import type { BreadcrumbItem } from '@/types/navigation';

export type AppLayoutProps = {
    breadcrumbs?: BreadcrumbItem[];
};

export type AppVariant = 'header' | 'sidebar';

export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
};

export type AuthLayoutProps = {
    name?: string;
    title?: string;
    description?: string;
};
