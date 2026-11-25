<x-layouts.auth
    title="Confirm password"
    heading="Confirm your password"
    description="This is a secure area of the application. Please confirm your password before continuing."
>
    <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-5">
        @csrf

        <div class="grid gap-2">
            <label for="password" class="text-sm font-medium leading-none">Password</label>
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

        <button
            type="submit"
            class="inline-flex h-10 w-full items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-xs transition-colors hover:bg-primary/90"
        >
            Confirm Password
        </button>
    </form>
</x-layouts.auth>
