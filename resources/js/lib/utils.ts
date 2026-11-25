import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export type UrlTarget = string | { url: string };

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: UrlTarget): string {
    return typeof url === 'string' ? url : url.url;
}
