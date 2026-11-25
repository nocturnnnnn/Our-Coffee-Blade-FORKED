@props(['canResetPassword' => true, 'status' => null])

<x-layouts.auth
    title="Log in"
    heading="Log in to your account"
    description="Enter your email and password below to log in"
>
    @if ($status)
        <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 p-3 text-center text-sm font-medium text-emerald-500">
            {{ $status }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
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

        <div class="grid gap-2">
            <div class="flex items-center justify-between">
                <label for="password" class="text-sm font-medium leading-none">Password</label>
                @if ($canResetPassword)
                    <a href="{{ route('password.request') }}" class="text-xs underline underline-offset-4 text-muted-foreground hover:text-foreground">
                        Forgot your password?
                    </a>
                @endif
            </div>
            <x-password-input
                id="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Password"
            />
            @error('password')
                <p class="text-xs font-medium text-destructive">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2.5 text-sm font-medium cursor-pointer select-none">
            <input
                type="checkbox"
                name="remember"
                value="1"
                @checked(old('remember'))
                class="size-4 rounded border border-input accent-primary"
            />
            <span>Remember me</span>
        </label>

        <button
            type="submit"
            class="inline-flex h-10 w-full items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-xs transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
        >
            Log in
        </button>
    </form>
</x-layouts.auth>
