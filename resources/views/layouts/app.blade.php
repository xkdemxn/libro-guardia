<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts.partials.head')
    </head>
    <body class="font-sans">
        <div class="fondo-noche" aria-hidden="true"><span></span></div>

        <div class="min-h-screen pb-16">
            @include('layouts.navigation')

            @isset($header)
                <header class="mx-auto max-w-7xl px-4 pt-10 sm:px-6 lg:px-8">
                    {{ $header }}
                </header>
            @endisset

            @if (session('status') || session('error'))
                <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8"
                     x-data="{ visible: true }" x-show="visible" x-transition.opacity.duration.300ms
                     x-init="setTimeout(() => visible = false, 6000)">
                    @if (session('status'))
                        <div role="status" class="vidrio flex items-center gap-3 !rounded-2xl px-5 py-3.5 text-sm text-emerald-200">
                            <span class="punto h-2 w-2 bg-emerald-400 shadow-[0_0_10px_rgb(52_211_153)]"></span>
                            {{ session('status') }}
                        </div>
                    @endif
                    @if (session('error'))
                        <div role="alert" class="vidrio flex items-center gap-3 !rounded-2xl px-5 py-3.5 text-sm text-rose-200">
                            <span class="punto h-2 w-2 bg-rose-400 shadow-[0_0_10px_rgb(251_113_133)]"></span>
                            {{ session('error') }}
                        </div>
                    @endif
                </div>
            @endif

            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
