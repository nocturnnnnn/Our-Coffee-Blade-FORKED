@props([
    'id' => null,
    'name' => 'password',
    'value' => '',
    'placeholder' => 'Password',
    'required' => false,
    'autocomplete' => 'current-password',
])

@php
    $inputId = $id ?? $name;
@endphp

<div x-data="{ show: false }" class="relative">
    <input
        id="{{ $inputId }}"
        name="{{ $name }}"
        :type="show ? 'text' : 'password'"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        autocomplete="{{ $autocomplete }}"
        @if ($required) required @endif
        {{ $attributes->merge([
            'class' => 'flex h-10 w-full rounded-lg border border-input bg-card px-3 py-2 pr-10 text-sm text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
        ]) }}
    />
    <button
        type="button"
        @click="show = !show"
        tabindex="-1"
        class="absolute inset-y-0 right-0 flex items-center px-3 text-muted-foreground hover:text-foreground"
        :aria-label="show ? 'Hide password' : 'Show password'"
    >
        <span x-show="!show"><x-icon name="eye" class="size-4" /></span>
        <span x-show="show" x-cloak><x-icon name="eye-off" class="size-4" /></span>
    </button>
</div>
