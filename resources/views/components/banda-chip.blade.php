{{-- Chip con un color propio para cada banda, para reconocerlas de un vistazo. --}}
@props(['banda'])

@php
$colores = [
    '160 m' => 'border-fuchsia-400/25 bg-fuchsia-400/10 text-fuchsia-300',
    '80 m' => 'border-purple-400/25 bg-purple-400/10 text-purple-300',
    '60 m' => 'border-violet-400/25 bg-violet-400/10 text-violet-300',
    '40 m' => 'border-indigo-400/25 bg-indigo-400/10 text-indigo-300',
    '30 m' => 'border-blue-400/25 bg-blue-400/10 text-blue-300',
    '20 m' => 'border-sky-400/25 bg-sky-400/10 text-sky-300',
    '17 m' => 'border-cyan-400/25 bg-cyan-400/10 text-cyan-300',
    '15 m' => 'border-teal-400/25 bg-teal-400/10 text-teal-300',
    '12 m' => 'border-emerald-400/25 bg-emerald-400/10 text-emerald-300',
    '10 m' => 'border-lime-400/25 bg-lime-400/10 text-lime-300',
    '6 m' => 'border-yellow-400/25 bg-yellow-400/10 text-yellow-300',
    '2 m' => 'border-amber-400/25 bg-amber-400/10 text-amber-300',
    '1.25 m' => 'border-orange-400/25 bg-orange-400/10 text-orange-300',
    '70 cm' => 'border-rose-400/25 bg-rose-400/10 text-rose-300',
    '33 cm' => 'border-red-400/25 bg-red-400/10 text-red-300',
    '23 cm' => 'border-fuchsia-400/25 bg-fuchsia-400/10 text-fuchsia-300',
];
@endphp

<span {{ $attributes->merge(['class' => 'chip font-mono '.($colores[$banda] ?? 'border-white/15 bg-white/5 text-slate-300')]) }}>{{ $banda }}</span>
