<!DOCTYPE html>
<html lang="tr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Predictive Inventory</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=bricolage-grotesque:500,600,700|ibm-plex-sans:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="grid min-h-screen lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]">
            {{-- Sol: tanıtım paneli (geniş ekranda) --}}
            <aside class="relative hidden overflow-hidden bg-ink p-12 text-white lg:flex lg:flex-col lg:justify-between">
                <div class="pointer-events-none absolute inset-0 opacity-[0.07]"
                     style="background-image: repeating-linear-gradient(to bottom, transparent 0 95px, #fff 95px 97px);"></div>

                <a href="/" class="relative flex items-center gap-2.5 font-display text-lg font-semibold">
                    <x-application-logo class="h-8 w-8 text-leaf-300" />
                    Predictive Inventory
                </a>

                <div class="relative max-w-md">
                    <p class="font-display text-3xl font-semibold leading-tight">
                        Ne kadar sipariş vereceğini bil, çöpe giden ürünü azalt.
                    </p>
                    <p class="mt-4 text-white/70">
                        Satış tahmini, sipariş önerisi ve fire riski; her ürün için tek sayfada.
                    </p>
                </div>

                <p class="relative text-sm text-white/45">KOBİ'ler için stok ve fire tahmini</p>
            </aside>

            {{-- Sağ: form --}}
            <main class="flex flex-col px-4 py-8 sm:px-6">
                <a href="/" class="flex items-center gap-2.5 font-display text-lg font-semibold text-ink lg:hidden">
                    <x-application-logo class="h-8 w-8 text-leaf-500" />
                    Predictive Inventory
                </a>

                <div class="flex flex-1 items-center justify-center py-10">
                    <div class="w-full max-w-md">
                        {{ $slot }}
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
