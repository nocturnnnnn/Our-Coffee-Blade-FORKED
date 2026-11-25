@props(['email' => '', 'token' => '', 'passwordRules' => null])

<x-layouts.auth
    title="Reset password"
    heading="Reset password"
    description="Please enter your new password below"
>
    <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}" />

        <div class="grid gap-2">
            <label for="email" class="text-sm font-medium leading-none">Email</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email', $email) }}"
                required
                autocomplete="email"
                readonly
                class="flex h-10 w-full rounded-lg border border-input bg-muted px-3 py-2 text-sm text-muted-foreground focus-visible:outline-none"
            />
            @error('email')
                <p class="text-xs font-medium text-destructive">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-2">
            <label for="password" class="text-sm font-medium leading-none">Password</label>
            <x-password-input
                id="password"
                name="password"
                required
                autocomplete="new-password"
                placeholder="Password"
            />
            @if ($passwordRules)
                <p class="text-xs text-muted-foreground">{{ $passwordRules }}</p>
            @endif
            @error('password')
                <p class="text-xs font-medium text-destructive">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-2">
            <label for="password_confirmation" class="text-sm font-medium leading-none">Confirm Password</label>
            <x-password-input
                id="password_confirmation"
                name="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="Confirm password"
            />
        </div>

        <button
            type="submit"
            class="inline-flex h-10 w-full items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-xs transition-colors hover:bg-primary/90"
        >
            Reset password
        </button>
    </form>
</x-layouts.auth>
