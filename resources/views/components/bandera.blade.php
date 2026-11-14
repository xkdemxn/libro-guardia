{{-- Bandera del país (archivos en public/banderas, de flag-icons). Si no hay código no muestra nada. --}}
@props(['codigo' => null, 'pais' => null])

@if ($codigo && is_file(public_path('banderas/'.$codigo.'.svg')))
    <img src="{{ asset('banderas/'.$codigo.'.svg') }}" alt="Bandera de {{ $pais ?? strtoupper($codigo) }}" title="{{ $pais }}"
         loading="lazy" width="20" height="15"
         {{ $attributes->merge(['class' => 'inline-block h-3.5 w-[1.15rem] shrink-0 rounded-[3px] object-cover align-[-2px] ring-1 ring-white/15']) }}>
@endif
