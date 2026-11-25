<x-layouts.base title="Welcome">
    <div class="flex min-h-screen flex-col items-center justify-center bg-background p-6 text-center">
        <div class="mb-4 flex size-14 items-center justify-center rounded-2xl bg-crema text-white shadow-md">
            <x-icon name="coffee" class="size-7" />
        </div>
        <h1 class="text-3xl font-bold tracking-tight">Our Coffee</h1>
        <p class="mt-2 max-w-md text-sm text-muted-foreground">
            Self-Ordering Kiosk, POS Kasir, Kitchen Display System, dan Backoffice Inventory.
        </p>
        <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
            @auth
                <a href="{{ route('dashboard') }}" class="rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground hover:bg-primary/90">
                    Buka Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground hover:bg-primary/90">
                    Masuk Staf
                </a>
            @endauth
            <a href="{{ route('kiosk.index') }}" class="rounded-xl border border-border bg-card px-5 py-2.5 text-sm font-semibold hover:bg-muted">
                Self-Ordering Kiosk
            </a>
        </div>
    </div>
</x-layouts.base>
