@props(['title' => 'Pengaturan Akun'])

@php
    $navLinks = [
        ['label' => 'Profil', 'route' => 'profile.edit', 'active' => 'profile.*'],
        ['label' => 'Keamanan & Kata Sandi', 'route' => 'security.edit', 'active' => 'security.*'],
        ['label' => 'Tampilan', 'route' => 'appearance.edit', 'active' => 'appearance.*'],
    ];
@endphp

<x-layouts.rail :title="$title">
    <div class="mx-auto max-w-5xl space-y-6">
        <div class="border-b border-border pb-4">
            <h1 class="text-2xl font-bold tracking-tight">Pengaturan Akun</h1>
            <p class="mt-1 text-sm text-muted-foreground">Kelola informasi profil, kata sandi, dan preferensi tema Anda.</p>
        </div>

        <div class="flex flex-col gap-6 lg:flex-row">
            <aside class="w-full shrink-0 lg:w-56">
                <nav class="flex flex-row gap-1 overflow-x-auto lg:flex-col">
                    @foreach ($navLinks as $link)
                        @php $active = request()->routeIs($link['active']); @endphp
                        <a
                            href="{{ route($link['route']) }}"
                            @class([
                                'whitespace-nowrap rounded-xl px-3 py-2 text-sm font-medium transition-colors',
                                'bg-primary text-primary-foreground shadow-xs' => $active,
                                'text-muted-foreground hover:bg-muted hover:text-foreground' => ! $active,
                            ])
                        >
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </nav>
            </aside>

            <div class="min-w-0 flex-1 space-y-6">
                {{ $slot }}
            </div>
        </div>
    </div>
</x-layouts.rail>
