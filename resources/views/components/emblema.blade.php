{{-- Emblema de Radioaficionados DX Ecuador (public/img). Decorativo por defecto: pon un alt si va solo, sin texto al lado. --}}
@props(['alt' => ''])

<img src="{{ asset('img/emblema-96.webp') }}"
     srcset="{{ asset('img/emblema-96.webp') }} 96w, {{ asset('img/emblema-192.webp') }} 192w, {{ asset('img/emblema-512.webp') }} 512w"
     sizes="(min-width: 1024px) 160px, 96px"
     alt="{{ $alt }}" width="96" height="96" decoding="async"
     {{ $attributes->merge(['class' => 'shrink-0 select-none drop-shadow-[0_4px_10px_rgb(0_0_0/0.35)]']) }}>
