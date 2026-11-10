<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#070e1c">

<title>{{ isset($titulo) ? $titulo.' · ' : '' }}{{ config('app.name', 'Libro de Guardia') }}</title>

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|unbounded:500,600|jetbrains-mono:500,600&display=swap" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
