<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-primary rounded-md border border-transparent bg-cyan-700 px-4 py-2 text-xs uppercase tracking-widest text-white hover:bg-cyan-800 focus:ring-cyan-600']) }} style="background-color: #0e7490; color: #ffffff; border-color: transparent;">
    {{ $slot }}
</button>
