<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-b from-marca-400 to-marca-600 px-5 py-2.5 text-sm font-semibold text-blanco shadow-boton transition hover:brightness-110 active:scale-[0.97] focus:outline-none focus-visible:ring-4 focus-visible:ring-acento-400/30 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
