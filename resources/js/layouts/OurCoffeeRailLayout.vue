<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    Boxes,
    ChefHat,
    Clock,
    Coffee,
    FolderTree,
    LayoutDashboard,
    LogOut,
    ScrollText,
    Settings,
    ShoppingBag,
    SlidersHorizontal,
    Store,
    Truck,
    Users,
    UtensilsCrossed,
} from 'lucide-vue-next';
import { computed, type Component } from 'vue';
import { formatRupiah, formatTimeWib } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ActiveShift, Auth, UserRole } from '@/types/auth';

type RailNavItem = {
    label: string;
    href: string;
    icon: Component;
    roles: UserRole[];
};

const NAV_ITEMS: RailNavItem[] = [
    { label: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard, roles: ['admin'] },
    { label: 'Kategori', href: '/admin/categories', icon: FolderTree, roles: ['admin'] },
    { label: 'Menu & BOM', href: '/admin/menus', icon: UtensilsCrossed, roles: ['admin'] },
    { label: 'Modifier', href: '/admin/modifiers', icon: SlidersHorizontal, roles: ['admin'] },
    { label: 'Inventaris', href: '/admin/inventory', icon: Boxes, roles: ['admin'] },
    { label: 'Distributor', href: '/admin/distributors', icon: Truck, roles: ['admin'] },
    { label: 'Laporan', href: '/admin/reports', icon: BarChart3, roles: ['admin'] },
    { label: 'Karyawan', href: '/admin/staff', icon: Users, roles: ['admin'] },
    { label: 'Audit Log', href: '/admin/audit-logs', icon: ScrollText, roles: ['admin'] },
    { label: 'Kiosk & Kafe', href: '/admin/settings', icon: Store, roles: ['admin'] },
    { label: 'POS Kasir', href: '/pos', icon: ShoppingBag, roles: ['admin', 'kasir'] },
    { label: 'Shift Kasir', href: '/pos/shift', icon: Clock, roles: ['admin', 'kasir'] },
    { label: 'Kitchen (KDS)', href: '/kds', icon: ChefHat, roles: ['admin', 'barista'] },
    { label: 'Pengaturan', href: '/settings/profile', icon: Settings, roles: ['admin', 'kasir', 'barista'] },
];

const page = usePage();

const user = computed(() => (page.props.auth as Auth | undefined)?.user);
const activeShift = computed(() => page.props.activeShift as ActiveShift | undefined);
const flash = computed(
    () =>
        page.props.flash as
            | { success?: string | null; error?: string | null }
            | undefined,
);
const currentUrl = computed(() => page.url.split('?')[0] ?? page.url);

const visibleNav = computed(() =>
    user.value
        ? NAV_ITEMS.filter((item) => item.roles.includes(user.value!.role))
        : [],
);

function isNavActive(href: string): boolean {
    return currentUrl.value === href || currentUrl.value.startsWith(`${href}/`);
}

function handleLogout() {
    router.post('/logout');
}
</script>

<template>
    <div class="flex min-h-screen w-full bg-background text-foreground">
        <aside class="flex w-20 shrink-0 flex-col justify-between border-r border-sidebar-border bg-sidebar text-sidebar-foreground lg:w-60">
            <div class="flex flex-col gap-5 p-3 lg:p-4">
                <div class="flex items-center gap-3 px-1 py-1">
                    <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-sidebar-primary text-sidebar-primary-foreground shadow-sm">
                        <Coffee class="size-6" />
                    </div>
                    <div class="hidden min-w-0 lg:block">
                        <p class="truncate text-base font-bold tracking-tight">
                            Our Coffee
                        </p>
                        <p class="truncate text-xs font-medium text-latte">
                            Cafe Operations
                        </p>
                    </div>
                </div>

                <nav aria-label="Navigasi Utama" class="flex flex-col gap-1">
                    <Link
                        v-for="item in visibleNav"
                        :key="item.href"
                        :href="item.href"
                        :class="
                            cn(
                                'flex min-h-11 items-center justify-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition-colors lg:justify-start',
                                isNavActive(item.href)
                                    ? 'bg-sidebar-primary text-sidebar-primary-foreground shadow-sm'
                                    : 'text-sidebar-foreground/80 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
                            )
                        "
                    >
                        <component :is="item.icon" class="size-5 shrink-0" />
                        <span class="hidden truncate lg:inline">
                            {{ item.label }}
                        </span>
                    </Link>
                </nav>
            </div>

            <div class="flex flex-col gap-3 border-t border-sidebar-border p-3 lg:p-4">
                <div
                    v-if="activeShift"
                    class="hidden rounded-xl bg-sidebar-accent px-3 py-2.5 text-xs lg:block"
                >
                    <p class="font-semibold text-latte">
                        Shift Aktif #{{ activeShift.id }}
                    </p>
                    <p class="font-money mt-0.5 text-sidebar-foreground/80">
                        Modal: {{ formatRupiah(activeShift.opening_cash) }}
                    </p>
                    <p class="mt-0.5 text-[11px] text-sidebar-foreground/60">
                        Buka {{ formatTimeWib(activeShift.opened_at) }} WIB
                    </p>
                </div>

                <div v-if="user" class="hidden px-1 lg:block">
                    <p class="truncate text-sm font-semibold">
                        {{ user.name }}
                    </p>
                    <p class="truncate text-xs font-semibold tracking-wider text-latte uppercase">
                        {{ user.role }}
                    </p>
                </div>

                <button
                    type="button"
                    @click="handleLogout"
                    class="flex min-h-11 w-full items-center justify-center gap-3 rounded-xl border border-sidebar-border px-3 py-2 text-sm font-medium text-sidebar-foreground/90 transition-colors hover:bg-sidebar-accent lg:justify-start"
                >
                    <LogOut class="size-5 shrink-0" />
                    <span class="hidden lg:inline">Keluar</span>
                </button>
            </div>
        </aside>

        <main class="flex min-w-0 flex-1 flex-col">
            <div v-if="flash?.error || flash?.success" class="px-6 pt-4">
                <div
                    v-if="flash.error"
                    class="rounded-xl border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm font-medium text-destructive"
                >
                    {{ flash.error }}
                </div>
                <div
                    v-if="flash.success"
                    class="rounded-xl border border-emerald-700/30 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900"
                >
                    {{ flash.success }}
                </div>
            </div>
            <div class="flex-1 p-6">
                <slot />
            </div>
        </main>
    </div>
</template>
