@php
    // Açılış sayfasındaki örnek tahmin kartı (gerçek veri değil, gösterim amaçlı)
    $sampleDays = [
        ['Pzt', 18, false], ['Sal', 16, false], ['Çar', 17, false], ['Per', 19, false],
        ['Cum', 24, false], ['Cmt', 34, true], ['Paz', 27, false],
    ];
    $sampleMax = max(array_column($sampleDays, 1));

    $steps = [
        ['Stok hareketlerini gir', 'Gelen malı, satışı ve fireyi tek tek gir ya da Excel\'den CSV dosyasıyla toplu yükle.'],
        ['Model satışlarını öğrensin', 'Haftanın günleri, mevsim ve kampanya günleri hesaba katılarak her ürün için satış tahmini yapılır.'],
        ['Önerileri uygula', 'Her ürün için ne kadar sipariş vermen gerektiğini ve fire riskini tek sayfada gör.'],
    ];

    $features = [
        ['Panel', 'Son 30 günün satışı, cirosu, fire oranı ve fire maliyeti. Stoğu azalan ürünler tek listede.', 'M3 13h4v7H3zM10 4h4v16h-4zM17 9h4v11h-4z'],
        ['7 günlük satış tahmini', 'Her ürün için önümüzdeki haftanın günlük satış beklentisi.', 'M3 17l6-6 4 4 8-8M14 7h7v7'],
        ['Sipariş önerisi', 'Elindeki stoğa ve beklenen satışa göre şimdi kaç adet sipariş vermen gerektiği.', 'M20 7 12 3 4 7m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
        ['Fire riski', 'Son kullanma tarihi yaklaşan stoktan ne kadarının satılamayacağı.', 'M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z'],
        ['Kampanya günleri', 'İndirim günlerini gir; tahmin o günlerde satışın artacağını hesaba katar.', 'M7 7h.01M7 3h5c.5 0 1 .2 1.4.6l7 7a2 2 0 0 1 0 2.8l-7 7a2 2 0 0 1-2.8 0l-7-7A2 2 0 0 1 3 12V7a4 4 0 0 1 4-4Z'],
        ['Her şirket kendi verisini görür', 'Aynı sistemi birden fazla işletme kullanabilir; veriler birbirine karışmaz.', 'M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2Zm10-10V7a4 4 0 0 0-8 0v4h8Z'],
    ];
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Predictive Inventory – KOBİ'ler için stok ve fire tahmini</title>
    <meta name="description" content="Marketler, fırınlar ve şarküteriler için satış tahmini, sipariş önerisi ve fire riski.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=bricolage-grotesque:500,600,700|ibm-plex-sans:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Sayfa açılırken örnek tahmin çubukları sırayla yükselir (tek animasyon) */
        @keyframes grow { from { transform: scaleY(0); } to { transform: scaleY(1); } }
        .grow-bar { transform-origin: bottom; animation: grow .7s cubic-bezier(.2, .8, .2, 1) both; }
        @media (prefers-reduced-motion: reduce) { .grow-bar { animation: none; } }
    </style>
</head>
<body class="font-sans antialiased">

    {{-- Üst menü --}}
    <header class="bg-ink text-white">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
            <a href="/" class="flex items-center gap-2.5 font-display text-lg font-semibold">
                <x-application-logo class="h-8 w-8 text-leaf-300" />
                Predictive Inventory
            </a>
            @if (Route::has('login'))
                <nav class="flex items-center gap-2">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn bg-leaf-300 text-ink hover:bg-leaf-200">Panele git</a>
                    @else
                        <a href="{{ route('login') }}" class="btn text-white/85 hover:bg-white/10 hover:text-white">Giriş yap</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn hidden bg-leaf-300 text-ink hover:bg-leaf-200 sm:inline-flex">Hesap oluştur</a>
                        @endif
                    @endauth
                </nav>
            @endif
        </div>
    </header>

    <main>
        {{-- Giriş bölümü --}}
        <section class="relative overflow-hidden bg-ink text-white">
            {{-- Arka planda raf çizgileri --}}
            <div class="pointer-events-none absolute inset-0 opacity-[0.07]"
                 style="background-image: repeating-linear-gradient(to bottom, transparent 0 95px, #fff 95px 97px);"></div>

            <div class="relative mx-auto grid max-w-6xl items-center gap-12 px-4 pb-20 pt-12 sm:px-6 lg:grid-cols-[1.1fr_1fr] lg:pb-28 lg:pt-20">
                <div>
                    <h1 class="font-display text-4xl font-bold leading-[1.05] tracking-tight sm:text-5xl lg:text-[3.6rem]">
                        Ne kadar sipariş vereceğini bil, çöpe giden ürünü azalt.
                    </h1>
                    <p class="mt-6 max-w-xl text-lg leading-relaxed text-white/75">
                        Predictive Inventory marketinin, fırınının ya da şarküterinin satışlarından öğrenir.
                        Her ürün için önümüzdeki 7 günün satışını, şimdi ne kadar sipariş vermen gerektiğini
                        ve tarihi geçmeden satılamayacak stoğu gösterir.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn bg-leaf-300 px-5 py-3 text-base text-ink hover:bg-leaf-200">Panele git</a>
                        @else
                            <a href="{{ route('login') }}" class="btn bg-leaf-300 px-5 py-3 text-base text-ink hover:bg-leaf-200">Giriş yap</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn border border-white/25 px-5 py-3 text-base text-white hover:bg-white/10">Hesap oluştur</a>
                            @endif
                        @endauth
                    </div>

                    {{-- Demo hesap bilgisi sadece kendi bilgisayarımızda (APP_ENV=local) görünür --}}
                    @env('local')
                        @guest
                            <p class="mt-5 text-sm text-white/55">
                                Denemek için demo hesap: <span class="text-white/85">{{ config('demo.email') }}</span>, şifre <span class="text-white/85">{{ config('demo.password') }}</span>
                            </p>
                        @endguest
                    @endenv
                </div>

                {{-- Örnek tahmin kartı --}}
                <div class="relative">
                    <div class="rounded-2xl bg-white p-5 text-ink shadow-lift sm:p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm text-ink-muted">Satış tahmini</p>
                                <p class="font-display text-xl font-semibold">Süt 1 L</p>
                            </div>
                            <span class="badge-gray">Önümüzdeki 7 gün</span>
                        </div>

                        <div class="mt-6 flex h-40 items-end gap-2 sm:gap-3" aria-hidden="true">
                            @foreach ($sampleDays as $i => [$day, $units, $promo])
                                <div class="flex h-full flex-1 flex-col items-center justify-end gap-1.5">
                                    <span class="text-xs tabular-nums text-ink-muted">{{ $units }}</span>
                                    <div class="grow-bar w-full rounded-t-md {{ $promo ? 'bg-mustard-500' : 'bg-leaf-500' }}"
                                         style="height: {{ round(100 * $units / $sampleMax) }}%; animation-delay: {{ 150 + $i * 70 }}ms"></div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-2 flex gap-2 border-t border-line pt-2 sm:gap-3" aria-hidden="true">
                            @foreach ($sampleDays as [$day])
                                <span class="flex-1 text-center text-xs text-ink-muted">{{ $day }}</span>
                            @endforeach
                        </div>
                        <p class="mt-3 flex items-center gap-2 text-xs text-ink-muted">
                            <span class="inline-block h-2.5 w-2.5 rounded-sm bg-mustard-500"></span>
                            Cumartesi kampanya var, satış artıyor
                        </p>

                        <div class="mt-5 grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-leaf-50 p-4">
                                <p class="text-sm text-leaf-800">Sipariş ver</p>
                                <p class="mt-1 font-display text-2xl font-semibold text-leaf-800">96 adet</p>
                            </div>
                            <div class="rounded-xl bg-tomato-50 p-4">
                                <p class="text-sm text-tomato-700">Fire riski</p>
                                <p class="mt-1 font-display text-2xl font-semibold text-tomato-700">4 adet</p>
                            </div>
                        </div>
                    </div>
                    <p class="mt-3 text-center text-xs text-white/45">Örnek görünüm</p>
                </div>
            </div>
        </section>

        {{-- Nasıl çalışır: gerçekten sıralı adımlar --}}
        <section class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
            <h2 class="font-display text-3xl font-semibold tracking-tight">Nasıl çalışır?</h2>
            <ol class="mt-10 grid gap-10 md:grid-cols-3 md:gap-8">
                @foreach ($steps as $i => [$title, $text])
                    <li class="relative">
                        <span class="font-display text-5xl font-bold text-leaf-200">{{ $i + 1 }}</span>
                        <h3 class="mt-2 text-lg font-semibold">{{ $title }}</h3>
                        <p class="mt-2 leading-relaxed text-ink-soft">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- Özellikler --}}
        <section class="border-y border-line bg-white">
            <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
                <div class="max-w-2xl">
                    <h2 class="font-display text-3xl font-semibold tracking-tight">Küçük işletmeler için yapıldı</h2>
                    <p class="mt-3 text-lg text-ink-soft">
                        Marketler, fırınlar ve şarküteriler siparişi çoğu zaman alışkanlıkla verir.
                        Bozulabilen ürünlerde bu ya fireye ya da rafın boş kalmasına yol açar.
                    </p>
                </div>

                <dl class="mt-12 grid gap-x-12 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($features as [$title, $text, $icon])
                        <div class="flex gap-4 border-t border-line py-6">
                            <svg class="mt-0.5 h-6 w-6 shrink-0 text-leaf-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="{{ $icon }}"/>
                            </svg>
                            <div>
                                <dt class="font-semibold">{{ $title }}</dt>
                                <dd class="mt-1 text-sm leading-relaxed text-ink-soft">{{ $text }}</dd>
                            </div>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>

        {{-- Son çağrı --}}
        <section class="mx-auto max-w-6xl px-4 py-20 text-center sm:px-6">
            <h2 class="mx-auto max-w-2xl font-display text-3xl font-semibold tracking-tight">
                Satış verini gir, ilk tahminini birkaç dakikada gör.
            </h2>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-primary px-5 py-3 text-base">Panele git</a>
                @else
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn-primary px-5 py-3 text-base">Hesap oluştur</a>
                    @endif
                    <a href="{{ route('login') }}" class="btn-secondary px-5 py-3 text-base">Giriş yap</a>
                @endauth
            </div>
        </section>
    </main>

    <footer class="border-t border-line">
        <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-8 text-sm text-ink-muted sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <span class="flex items-center gap-2">
                <x-application-logo class="h-5 w-5 text-leaf-500" />
                Predictive Inventory
            </span>
            <span>KOBİ'ler için stok ve fire tahmini. Mezuniyet projesi, {{ date('Y') }}.</span>
        </div>
    </footer>
</body>
</html>
