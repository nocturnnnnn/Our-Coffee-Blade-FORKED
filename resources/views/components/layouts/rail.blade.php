@props(['title' => ''])

@php
    $user = auth()->user();
    $roleValue = $user?->role?->value ?? 'kasir';

    $navItems = [];
    if ($roleValue === 'admin') {
        $navItems[] = ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'icon' => 'layout-dashboard'];
        $navItems[] = ['label' => 'Kategori', 'route' => 'admin.categories.index', 'active' => 'admin.categories.*', 'icon' => 'folder-tree'];
        $navItems[] = ['label' => 'Menu & BOM', 'route' => 'admin.menus.index', 'active' => 'admin.menus.*', 'icon' => 'utensils-crossed'];
        $navItems[] = ['label' => 'Modifier', 'route' => 'admin.modifiers.index', 'active' => 'admin.modifiers.*', 'icon' => 'sliders-horizontal'];
        $navItems[] = ['label' => 'Inventaris', 'route' => 'admin.inventory.index', 'active' => 'admin.inventory.*', 'icon' => 'boxes'];
        $navItems[] = ['label' => 'Distributor', 'route' => 'admin.distributors.index', 'active' => 'admin.distributors.*', 'icon' => 'truck'];
        $navItems[] = ['label' => 'Laporan', 'route' => 'admin.reports.index', 'active' => 'admin.reports.*', 'icon' => 'bar-chart-3'];
        $navItems[] = ['label' => 'Karyawan', 'route' => 'admin.staff.index', 'active' => 'admin.staff.*', 'icon' => 'users'];
        $navItems[] = ['label' => 'Audit Log', 'route' => 'admin.audit-logs.index', 'active' => 'admin.audit-logs.*', 'icon' => 'scroll-text'];
        $navItems[] = ['label' => 'Kiosk & Kafe', 'route' => 'admin.settings.index', 'active' => 'admin.settings.*', 'icon' => 'store'];
    }

    if (in_array($roleValue, ['admin', 'kasir'], true)) {
        $navItems[] = ['label' => 'POS Kasir', 'route' => 'pos.index', 'active' => 'pos.index', 'icon' => 'receipt'];
        $navItems[] = ['label' => 'Shift Kasir', 'route' => 'pos.shift', 'active' => 'pos.shift', 'icon' => 'clock'];
    }

    if (in_array($roleValue, ['admin', 'barista'], true)) {
        $navItems[] = ['label' => 'Kitchen (KDS)', 'route' => 'kds.index', 'active' => 'kds.index', 'icon' => 'chef-hat'];
    }

    $navItems[] = ['label' => 'Pengaturan', 'route' => 'profile.edit', 'active' => ['profile.*', 'security.*', 'appearance.*'], 'icon' => 'settings'];
@endphp

<x-layouts.base :title="$title">
    <div x-data="{ mobileOpen: false }" class="flex min-h-screen bg-background text-foreground">
        {{-- Mobile backdrop --}}
        <div
            x-show="mobileOpen"
            x-cloak
            @click="mobileOpen = false"
            class="fixed inset-0 z-40 bg-black/60 backdrop-blur-xs lg:hidden"
        ></div>

        {{-- Sidebar --}}
        <aside
            :class="mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="fixed inset-y-0 left-0 z-50 flex w-64 shrink-0 flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground transition-transform duration-200 lg:static"
        >
            {{-- Brand --}}
            <div class="flex items-center justify-between border-b border-sidebar-border px-4 py-4">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-xl bg-crema text-white shadow-sm">
                        <x-icon name="coffee" class="size-5" />
                    </div>
                    <div>
                        <div class="text-sm font-bold tracking-tight text-sidebar-foreground">Our Coffee</div>
                        <div class="text-[11px] text-sidebar-foreground/60">Cafe Operations</div>
                    </div>
                </a>
                <button
                    type="button"
                    @click="mobileOpen = false"
                    class="rounded-lg p-1.5 text-sidebar-foreground/70 hover:bg-sidebar-accent lg:hidden"
                >
                    <x-icon name="x" class="size-4" />
                </button>
            </div>

            {{-- Nav Links --}}
            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                @foreach ($navItems as $item)
                    @php
                        $isActive = request()->routeIs($item['active']);
                    @endphp
                    <a
                        href="{{ route($item['route']) }}"
                        @class([
                            'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors',
                            'bg-sidebar-primary text-sidebar-primary-foreground shadow-sm' => $isActive,
                            'text-sidebar-foreground/80 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground' => ! $isActive,
                        ])
                    >
                        <x-icon :name="$item['icon']" class="size-4 shrink-0" />
                        <span class="truncate">{{ $item['label'] }}</span>
                    </a>
                @endforeach

                <div class="pt-3 mt-3 border-t border-sidebar-border/70">
                    <a
                        href="{{ route('display.queue') }}"
                        target="_blank"
                        class="flex items-center justify-between gap-2 rounded-xl px-3 py-2 text-xs font-medium text-sidebar-foreground/70 transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                    >
                        <span class="flex items-center gap-2.5">
                            <x-icon name="monitor" class="size-4 shrink-0" />
                            <span>Layar Antrean</span>
                        </span>
                        <x-icon name="external-link" class="size-3.5 opacity-60" />
                    </a>
                </div>
            </nav>

            {{-- Footer / Active Shift + Profile + Logout --}}
            <div class="space-y-3 border-t border-sidebar-border p-3">
                @if (isset($activeShift) && $activeShift !== null)
                    <a
                        href="{{ route('pos.shift') }}"
                        class="block rounded-xl border border-amber-500/25 bg-sidebar-accent/70 p-3 text-xs transition-colors hover:bg-sidebar-accent"
                    >
                        <div class="font-semibold text-amber-400">Shift Aktif #{{ $activeShift['id'] }}</div>
                        <div class="mt-0.5 font-money text-sidebar-foreground/90">
                            Modal: Rp {{ number_format($activeShift['opening_cash'], 0, ',', '.') }}
                        </div>
                        <div class="mt-0.5 text-[11px] text-sidebar-foreground/60">
                            Buka {{ \Carbon\Carbon::parse($activeShift['opened_at'])->setTimezone('Asia/Jakarta')->format('H.i') }} WIB
                        </div>
                    </a>
                @endif

                <div class="px-1">
                    <div class="truncate text-sm font-semibold text-sidebar-foreground">{{ $user?->name }}</div>
                    <div class="text-[11px] font-semibold tracking-wider text-sidebar-foreground/60 uppercase">
                        {{ $user?->role?->value ?? 'STAFF' }}
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-sidebar-border bg-sidebar-accent/50 px-3 py-2 text-xs font-semibold text-sidebar-foreground transition-colors hover:bg-sidebar-accent"
                    >
                        <x-icon name="log-out" class="size-3.5" />
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main content area --}}
        <div class="flex min-w-0 flex-1 flex-col">
            {{-- Mobile top header --}}
            <header class="sticky top-0 z-30 flex items-center justify-between border-b border-border bg-background/95 px-4 py-3 backdrop-blur-sm lg:hidden">
                <button
                    type="button"
                    @click="mobileOpen = true"
                    class="inline-flex items-center gap-2 rounded-lg border border-border bg-card px-3 py-1.5 text-xs font-semibold"
                >
                    <x-icon name="coffee" class="size-4 text-crema" />
                    <span>Menu</span>
                </button>
                <span class="text-sm font-semibold">{{ $title ?: 'Our Coffee' }}</span>
                <a href="{{ route('profile.edit') }}" class="rounded-lg border border-border bg-card p-1.5">
                    <x-icon name="settings" class="size-4" />
                </a>
            </header>

            <main class="flex-1 p-4 md:p-6 lg:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>
</x-layouts.base>
