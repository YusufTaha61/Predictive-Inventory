<!DOCTYPE html>
<html lang="tr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title . ' – ' : '' }}Predictive Inventory</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=bricolage-grotesque:500,600,700|ibm-plex-sans:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div x-data="{ menuOpen: false }" class="min-h-screen lg:flex">
            @include('layouts.navigation')

            <div class="min-w-0 flex-1">
                <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-10 lg:py-10">
                    @isset($header)
                        <div class="mb-6 lg:mb-8">
                            {{ $header }}
                        </div>
                    @endisset

                    <div class="space-y-6">
                        <x-flash />
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
