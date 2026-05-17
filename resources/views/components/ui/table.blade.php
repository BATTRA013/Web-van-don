@props([])

<div class="table-surface">
    <table {{ $attributes->merge(['class' => 'min-w-full divide-y divide-slate-200 text-sm']) }}>
        {{ $slot }}
    </table>
</div>