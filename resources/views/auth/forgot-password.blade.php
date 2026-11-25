@props(['status' => null])

<x-layouts.auth
    title="Forgot password"
    heading="Forgot password"
    description="Enter your email to receive a password reset link"
>
    @if ($status)
        <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 p-3 text-center text-sm font-medium text-emerald-500">
            {{ $status }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5">
        @csrf

        <div class="grid gap-2">
            <label for="email" class="text-sm font-medium leading-none">Email address</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
                class="flex h-10 w-full rounded-lg border border-input bg-card px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            />
            @error('email')
                <p class="text-xs font-medium text-destructive">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="inline-flex h-10 w-full items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-xs transition-colors hover:bg-primary/90"
        >
            Email password reset link
        </button>

        <div class="text-center text-sm text-muted-foreground">
            <span>Or, return to</span>
            <a href="{{ route('login') }}" class="font-medium text-foreground underline underline-offset-4">log in</a>
        </div>
    </form>
</x-layouts.auth>
