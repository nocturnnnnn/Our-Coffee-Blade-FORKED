import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h, type DefineComponent } from 'vue';
import Toaster from '@/components/ui/Toaster.vue';
import { initializeTheme } from '@/composables/useAppearance';
import { useFlashToast } from '@/composables/useFlashToast';
import AppLayout from '@/layouts/AppLayout.vue';
import OurCoffeeRailLayout from '@/layouts/OurCoffeeRailLayout.vue';
import SettingsLayout from '@/layouts/SettingsLayout.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Our Coffee';

function resolveLayout(name: string) {
    if (
        name === 'welcome' ||
        name.startsWith('auth/') ||
        name.startsWith('kiosk/') ||
        name.startsWith('display/')
    ) {
        return undefined;
    }
    if (
        name.startsWith('admin/') ||
        name.startsWith('pos/') ||
        name.startsWith('kds/')
    ) {
        return OurCoffeeRailLayout;
    }
    if (name.startsWith('settings/')) {
        return [OurCoffeeRailLayout, SettingsLayout];
    }
    return AppLayout;
}

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: async (name) => {
        const page = await resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        );
        if (page.default.layout === undefined) {
            const layout = resolveLayout(name);
            if (layout !== undefined) {
                page.default.layout = layout;
            }
        }
        return page;
    },
    setup({ el, App, props, plugin }) {
        const Root = {
            setup() {
                useFlashToast();
                return () =>
                    h('div', { class: 'contents' }, [h(App, props), h(Toaster)]);
            },
        };
        createApp(Root).use(plugin).mount(el);
    },
    progress: {
        color: '#b45309',
    },
});

initializeTheme();
