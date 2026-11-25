@props(['title' => '', 'heading' => '', 'description' => ''])

<x-layouts.base :title="$title">
    <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-6">
                <div class="flex flex-col items-center gap-3 text-center">
                    <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium">
                        <div class="flex size-12 items-center justify-center rounded-xl bg-crema text-white shadow-sm">
                            <x-icon name="coffee" class="size-6" />
                        </div>
                        <div class="space-y-0.5 text-center">
                            <div class="text-base font-bold tracking-tight text-foreground">Our Coffee</div>
                            <div class="text-xs text-muted-foreground">Cafe Operations Platform</div>
                        </div>
                    </a>

                    @if ($heading !== '' || $description !== '')
                        <div class="mt-2 space-y-1 text-center">
                            @if ($heading !== '')
                                <h1 class="text-xl font-semibold tracking-tight text-foreground">{{ $heading }}</h1>
                            @endif
                            @if ($description !== '')
                                <p class="text-center text-sm text-muted-foreground">{{ $description }}</p>
                            @endif
                        </div>
                    @endif
                </div>

                {{ $slot }}
            </div>
        </div>
    </div>
</x-layouts.base>
