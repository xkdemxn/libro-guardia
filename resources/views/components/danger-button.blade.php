<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-b from-rose-500 to-rose-600 px-5 py-2.5 text-sm font-semibold text-blanco shadow-[inset_0_1px_0_0_rgb(255_255_255/0.25),0_8px_24px_-8px_rgb(244_63_94/0.6)] transition hover:brightness-110 active:scale-[0.97] focus:outline-none focus-visible:ring-4 focus-visible:ring-rose-500/30']) }}>
    {{ $slot }}
</button>
