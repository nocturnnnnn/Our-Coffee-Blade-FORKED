import { computed, ref } from 'vue';

export type ResolvedAppearance = 'light' | 'dark';
export type Appearance = ResolvedAppearance | 'system';

const currentAppearance = ref<Appearance>('system');

const prefersDark = (): boolean => {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches;
};

const setCookie = (name: string, value: string, days = 365): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const getStoredAppearance = (): Appearance => {
    if (typeof window === 'undefined') {
        return 'system';
    }

    return (localStorage.getItem('appearance') as Appearance) || 'system';
};

const isDarkMode = (appearance: Appearance): boolean => {
    return appearance === 'dark' || (appearance === 'system' && prefersDark());
};

const applyTheme = (appearance: Appearance): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const isDark = isDarkMode(appearance);

    document.documentElement.classList.toggle('dark', isDark);
    document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
};

export function initializeTheme(): void {
    if (typeof window === 'undefined') {
        return;
    }

    if (!localStorage.getItem('appearance')) {
        localStorage.setItem('appearance', 'system');
        setCookie('appearance', 'system');
    }

    currentAppearance.value = getStoredAppearance();
    applyTheme(currentAppearance.value);

    window
        .matchMedia('(prefers-color-scheme: dark)')
        .addEventListener('change', () => applyTheme(currentAppearance.value));
}

export function useAppearance() {
    const resolvedAppearance = computed<ResolvedAppearance>(() =>
        isDarkMode(currentAppearance.value) ? 'dark' : 'light',
    );

    const updateAppearance = (mode: Appearance): void => {
        currentAppearance.value = mode;
        localStorage.setItem('appearance', mode);
        setCookie('appearance', mode);
        applyTheme(mode);
    };

    return {
        appearance: currentAppearance,
        resolvedAppearance,
        updateAppearance,
    };
}
