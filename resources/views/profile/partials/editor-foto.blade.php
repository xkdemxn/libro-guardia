{{--
    Editor para recortar la foto de perfil: arrastrar para mover, deslizador o rueda del mouse para acercar.
    Usa el estado del componente fotoPerfil (resources/js/app.js). Va al <body> con x-teleport porque el
    desenfoque de los paneles de vidrio rompe el posicionamiento fijo de lo que tienen dentro.
--}}
<template x-teleport="body">
    <div x-show="editor.abierto" x-cloak x-transition.opacity.duration.200ms
         class="fixed inset-0 z-[60] grid place-items-center overflow-y-auto bg-noche-950/70 p-4 backdrop-blur-sm"
         x-on:keydown.escape.window="editor.abierto && cerrarEditor()"
         role="dialog" aria-modal="true" aria-labelledby="titulo-editor-foto">
        <div class="vidrio-fuerte w-full max-w-md rounded-3xl p-6" x-on:click.outside="cerrarEditor()">
            <h2 id="titulo-editor-foto" class="font-display text-lg font-semibold text-white">Ajustar foto</h2>
            <p class="mt-1 text-sm text-slate-400">Arrastra la foto para moverla y usa el deslizador para acercarla.</p>

            {{-- Área de recorte: lo que se ve dentro del cuadro es lo que se guarda --}}
            <div class="mt-5 flex justify-center">
                <div class="relative cursor-grab touch-none select-none overflow-hidden rounded-[2.5rem] bg-noche-900 ring-1 ring-white/15 active:cursor-grabbing"
                     :style="`width: ${LADO}px; height: ${LADO}px`"
                     x-on:pointerdown.prevent="empezarArrastre($event)"
                     x-on:pointermove="arrastrar($event)"
                     x-on:pointerup="soltar()"
                     x-on:pointercancel="soltar()"
                     x-on:wheel.prevent="rueda($event)">
                    <img :src="editor.src" alt="" draggable="false"
                         class="pointer-events-none absolute left-0 top-0 max-w-none origin-top-left"
                         :style="`width: ${editor.ancho}px; height: ${editor.alto}px; transform: translate(${editor.x}px, ${editor.y}px) scale(${editor.escala})`">

                    {{-- Guías de tercios, solo mientras se arrastra --}}
                    <div class="pointer-events-none absolute inset-0 transition-opacity duration-200" :class="arrastre ? 'opacity-100' : 'opacity-0'">
                        <div class="absolute inset-y-0 left-1/3 w-px bg-blanco/50"></div>
                        <div class="absolute inset-y-0 left-2/3 w-px bg-blanco/50"></div>
                        <div class="absolute inset-x-0 top-1/3 h-px bg-blanco/50"></div>
                        <div class="absolute inset-x-0 top-2/3 h-px bg-blanco/50"></div>
                    </div>
                </div>
            </div>

            {{-- Zoom --}}
            <div class="mt-5 flex items-center gap-3">
                <button type="button" x-on:click="acercar(1 / 1.15)" class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-slate-300 ring-1 ring-white/10 transition hover:bg-white/10" aria-label="Alejar">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4 10a.75.75 0 0 1 .75-.75h10.5a.75.75 0 0 1 0 1.5H4.75A.75.75 0 0 1 4 10Z" clip-rule="evenodd" /></svg>
                </button>
                <label for="zoom-foto" class="sr-only">Acercar</label>
                <input id="zoom-foto" type="range" min="100" max="400" step="1"
                       :value="Math.round(editor.escala / editor.escalaMinima * 100)"
                       x-on:input="acercarA(editor.escalaMinima * $event.target.value / 100)"
                       class="h-1.5 flex-1 cursor-pointer accent-marca-500">
                <button type="button" x-on:click="acercar(1.15)" class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-slate-300 ring-1 ring-white/10 transition hover:bg-white/10" aria-label="Acercar">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" /></svg>
                </button>
            </div>

            <div class="mt-6 flex items-center justify-between gap-3">
                <button type="button" x-on:click="centrar()" class="text-sm text-slate-400 transition hover:text-white">Restablecer</button>
                <div class="flex gap-2">
                    <x-secondary-button type="button" x-on:click="cerrarEditor()">Cancelar</x-secondary-button>
                    <x-primary-button type="button" x-on:click="aplicarRecorte()">Usar esta foto</x-primary-button>
                </div>
            </div>
        </div>
    </div>
</template>
