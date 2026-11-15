<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#070e1c">
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" type="image/png" href="{{ asset('favicon-32.png') }}" sizes="32x32">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

{{-- Aplica el tema guardado (o el del sistema) antes de pintar, para que la página no parpadee. --}}
<script>
    (() => {
        let tema = null;
        try { tema = localStorage.getItem('tema'); } catch (e) {}
        const claro = tema ? tema === 'claro' : window.matchMedia('(prefers-color-scheme: light)').matches;
        document.documentElement.classList.toggle('claro', claro);
        document.querySelector('meta[name="theme-color"]').content = claro ? '#e8eef8' : '#070e1c';
    })();
</script>

<title>{{ isset($titulo) ? $titulo.' · ' : '' }}{{ config('app.name', 'Libro de Guardia') }}</title>

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|unbounded:500,600|jetbrains-mono:500,600&display=swap" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])

@stack('head')
