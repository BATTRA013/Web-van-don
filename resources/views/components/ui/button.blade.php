@props([
    'type' => 'button',
    'variant' => 'primary',
])

@php
    $variants = [
        'primary' => 'btn-primary text-white',
        'secondary' => 'btn-secondary text-slate-700',
        'success' => 'btn-success text-white',
        'danger' => 'btn-danger text-white',
    ];

    $variantClass = $variants[$variant] ?? $variants['primary'];
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => $variantClass]) }}
>
    {{ $slot }}
</button>