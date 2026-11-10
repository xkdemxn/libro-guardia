<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 rounded-full border border-white/15 bg-white/5 px-5 py-2.5 text-sm font-semibold text-slate-100 backdrop-blur-xl transition hover:bg-white/10 active:scale-[0.97] focus:outline-none focus-visible:ring-4 focus-visible:ring-white/15 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
