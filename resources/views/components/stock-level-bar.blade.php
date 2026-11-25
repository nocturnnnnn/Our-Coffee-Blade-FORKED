@props([
    'current' => 0,
    'min' => 0,
])

@php
    $currentVal = (float) $current;
    $minVal = max(0.001, (float) $min);
    $ratio = $currentVal / ($minVal * 2);
    $percent = (int) max(0, min(100, round($ratio * 100)));
    $isLow = $currentVal <= (float) $min;
    $isWarning = ! $isLow && $currentVal <= ((float) $min * 1.5);
@endphp

<div class="h-2 w-full overflow-hidden rounded-full bg-muted">
    <div
        @class([
            'h-full rounded-full transition-all',
            'bg-red-500' => $isLow,
            'bg-amber-500' => $isWarning,
            'bg-emerald-500' => ! $isLow && ! $isWarning,
        ])
        style="width: {{ $percent }}%"
    ></div>
</div>
