import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { toUrl, type UrlTarget } from '@/lib/utils';

export function useCurrentUrl() {
    const page = usePage();

    const currentUrl = computed(() => {
        return new URL(
            page.url,
            typeof window !== 'undefined'
                ? window.location.origin
                : 'http://localhost',
        ).pathname;
    });

    const isCurrentUrl = (
        urlToCheck: UrlTarget,
        targetUrl?: string,
        startsWith = false,
    ): boolean => {
        const urlToCompare = targetUrl ?? currentUrl.value;
        const urlString = toUrl(urlToCheck);

        const comparePath = (path: string): boolean =>
            startsWith ? urlToCompare.startsWith(path) : path === urlToCompare;

        if (!urlString.startsWith('http')) {
            return comparePath(urlString);
        }

        try {
            const absoluteUrl = new URL(urlString);
            return comparePath(absoluteUrl.pathname);
        } catch {
            return false;
        }
    };

    const isCurrentOrParentUrl = (
        urlToCheck: UrlTarget,
        targetUrl?: string,
    ): boolean => {
        return isCurrentUrl(urlToCheck, targetUrl, true);
    };

    return {
        currentUrl,
        isCurrentUrl,
        isCurrentOrParentUrl,
    };
}
