{{-- Tarjeta de vidrio oscuro, con ceja opcional arriba. --}}
@props(['ceja' => null])

<section {{ $attributes->merge(['class' => 'vidrio p-5 sm:p-6']) }}>
    @if ($ceja)
        <x-ceja class="mb-5">{{ $ceja }}</x-ceja>
    @endif
    {{ $slot }}
</section>
