<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn-secondary rounded-md px-4 py-2 text-xs uppercase tracking-widest']) }}>
    {{ $slot }}
</button>
