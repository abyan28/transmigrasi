@props([
    'kolom' => 4,
])

@php
    $gridCols = match ((int) $kolom) {
        2 => 'grid grid-cols-2 divide-x divide-gray-200 dark:divide-gray-800',
        3 => 'grid grid-cols-1 divide-y divide-gray-200 sm:grid-cols-3 sm:divide-y-0 sm:divide-x dark:divide-gray-800',
        default => 'grid grid-cols-2 divide-y divide-gray-200 sm:grid-cols-2 sm:divide-y-0 sm:divide-x lg:grid-cols-4 dark:divide-gray-800',
    };
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border border-gray-200 bg-white shadow-2xs dark:border-gray-800 dark:bg-white/[0.03]']) }}>
    <div class="{{ $gridCols }}">
        {{ $slot }}
    </div>
</div>
