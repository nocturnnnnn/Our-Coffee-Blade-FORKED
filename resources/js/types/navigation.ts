import type { Component } from 'vue';
import type { UrlTarget } from '@/lib/utils';

export type BreadcrumbItem = {
    title: string;
    href: UrlTarget;
};

export type NavItem = {
    title: string;
    href: UrlTarget;
    icon?: Component | null;
    isActive?: boolean;
};
