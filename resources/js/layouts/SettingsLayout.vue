<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import Separator from '@/components/ui/Separator.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { cn, toUrl } from '@/lib/utils';

const { isCurrentOrParentUrl } = useCurrentUrl();

const sidebarNavItems = [
    { title: 'Profile', href: '/settings/profile' },
    { title: 'Security', href: '/settings/security' },
    { title: 'Appearance', href: '/settings/appearance' },
];
</script>

<template>
    <div class="px-4 py-6">
        <Heading
            title="Settings"
            description="Manage your profile and account settings"
        />

        <div class="flex flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48">
                <nav class="flex flex-col space-y-1 space-x-0" aria-label="Settings">
                    <Link
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        :href="item.href"
                        :class="
                            cn(
                                'inline-flex h-8 w-full items-center justify-start rounded-md px-3 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground',
                                isCurrentOrParentUrl(item.href) ? 'bg-muted' : '',
                            )
                        "
                    >
                        {{ item.title }}
                    </Link>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1 md:max-w-2xl">
                <section class="max-w-xl space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
