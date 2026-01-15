<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'CONTROL DE ARMAS') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('images/controlarmasdev.png') }}">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
    <style>
        html {
            scrollbar-gutter: stable;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>

</head>



<body
    class="font-sans antialiased overflow-hidden md:overflow-hidden selection:bg-orange-500/30
             bg-[#0e1013] text-white overscroll-none">

    <div class="relative min-h-screen">

        <div class="absolute inset-0 -z-20"
            style="
                background-image:
                  radial-gradient(ellipse at 20% 30%, rgba(255,255,255,0.06) 0%, rgba(255,255,255,0.025) 28%, transparent 48%),
                  radial-gradient(ellipse at 80% 70%, rgba(255,255,255,0.06) 0%, rgba(255,255,255,0.025) 28%, transparent 48%),
                  radial-gradient(rgba(255,255,255,0.045) 1px, transparent 1px),
                  radial-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                  linear-gradient(180deg, rgba(0,0,0,0.35), rgba(0,0,0,0.68));
                background-size: 100% 100%, 100% 100%, 3px 3px, 4px 4px, 100% 100%;
                background-position: center, center, 0 0, 1px 2px, 0 0;
                filter: contrast(1.05) saturate(0.9);
             ">
        </div>


        <div class="absolute inset-0 -z-10 pointer-events-none"
            style="background: radial-gradient(62% 62% at 50% 38%, rgba(0,0,0,0) 0%, rgba(0,0,0,.34) 55%, rgba(0,0,0,.82) 100%);">
        </div>

        <div class="pointer-events-none absolute inset-0 -z-10 flex items-center justify-center">
            <img src="{{ asset('images/controlarmasdev.png') }}" alt="CONTROL DE ARMAS"
                class="w-[90vw] max-w-[1200px] min-w-[400px]
                object-contain mx-auto
                opacity-[0.18] md:opacity-[0.22]
                contrast-125 brightness-110
                drop-shadow-[0_0_110px_rgba(255,120,40,0.35)]">
        </div>

        <main class="relative z-10 grid min-h-screen place-items-center p-4 overflow-y-auto md:overflow-visible">
            <div class="w-full max-w-md">
                {{ $slot }}
            </div>
        </main>

        <footer class="absolute bottom-0 left-0 right-0 z-10 border-t border-white/10 bg-black/30 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/controlarmasdev.png') }}" alt="CONTROL DE ARMAS"
                        class="h-7 w-7 rounded bg-white/10 p-1 ring-1 ring-white/10">
                    <div class="flex flex-col">
                        <span class="text-sm font-semibold leading-none">CONTROL DE ARMAS</span>
                        <a href="https://www.develotechgt.com/" target="_blank" class="text-[0.6rem] text-slate-400 font-medium tracking-wider uppercase hover:text-white transition-colors">by Develotech</a>
                    </div>
                </div>
                <span class="text-xs text-slate-300/80">© {{ date('Y') }} • Acceso interno</span>
            </div>
        </footer>

    </div>
    @stack('scripts')
</body>

</html>
