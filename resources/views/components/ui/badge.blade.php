@props([
    'status' => 'default',
    'label' => null,
])

@php
    $styles = [
        'moi' => 'status-badge status-badge-info',
        'cho_lay_hang' => 'status-badge status-badge-pending',
        'dang_van_chuyen' => 'status-badge status-badge-info',
        'da_giao' => 'status-badge status-badge-success',
        'hoan' => 'status-badge status-badge-danger',
        'cho_duyet' => 'status-badge status-badge-pending',
        'da_duyet' => 'status-badge status-badge-success',
        'tu_choi' => 'status-badge status-badge-danger',
        'default' => 'status-badge status-badge-default',
    ];

    $state = $styles[$status] ?? $styles['default'];
    $displayLabel = $label ?? str($status)->replace('_', ' ')->title();
@endphp

<span {{ $attributes->merge(['class' => $state]) }}>
    {{ $displayLabel }}
</span>