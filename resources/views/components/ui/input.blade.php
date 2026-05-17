@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'type' => 'text',
])

@php
    $fieldId = $id ?? ($name ? "field_{$name}" : uniqid('field_'));
@endphp

<div>
    @if ($label)
        <label for="{{ $fieldId }}" class="mb-1.5 block text-sm font-medium text-gray-700">{{ $label }}</label>
    @endif

    <input
        id="{{ $fieldId }}"
        type="{{ $type }}"
        @if ($name) name="{{ $name }}" @endif
        {{ $attributes->class('w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500') }}
    >

    @if ($name)
        @error($name)
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    @endif
</div>