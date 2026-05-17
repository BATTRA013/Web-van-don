<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-danger rounded-md px-4 py-2 text-xs uppercase tracking-widest']) }}>
    {{ $slot }}
</button>
