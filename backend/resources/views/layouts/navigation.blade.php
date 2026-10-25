@php
    // Menü öğeleri: [route, aktif sayılacak route deseni, yazı, ikon yolu]
    $menu = [
        ['dashboard', 'dashboard', 'Panel', 'M3 13h4v7H3zM10 4h4v16h-4zM17 9h4v11h-4z'],
        ['products.index', 'products.*', 'Ürünler', 'M20 7 12 3 4 7m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
        ['stock-movements.index', 'stock-movements.*', 'Stok Hareketleri', 'M7 16V4m0 0L3 8m4-4 4 4m6 0v12m0 0 4-4m-4 4-4-4'],
        ['promotions.index', 'promotions.*', 'Kampanyalar', 'M7 7h.01M7 3h5c.5 0 1 .2 1.4.6l7 7a2 2 0 0 1 0 2.8l-7 7a2 2 0 0 1-2.8 0l-7-7A2 2 0 0 1 3 12V7a4 4 0 0 1 4-4Z'],
    ];
    $user = Auth::user();
    $initials = mb_strtoupper(collect(explode(' ', trim($user->name)))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->join(''));
@endphp

{{-- Mobil: üst çubuk --}}
<div class="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-white/10 bg-ink px-4 text-white lg:hidden">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 font-display font-semibold">
        <x-application-logo class="h-7 w-7 text-leaf-300" />
        Predictive Inventory
    </a>
    <button type="button" @click="menuOpen = true"
            class="rounded-lg p-2 text-white/80 hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-leaf-300"
            aria-label="Menüyü aç">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
</div>

{{-- Mobilde menü açıkken arkadaki karartma --}}
<div x-show="menuOpen" x-cloak x-transition.opacity @click="menuOpen = false"
     class="fixed inset-0 z-40 bg-ink/50 lg:hidden"></div>

{{-- Kenar menü --}}
<aside :class="menuOpen ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col bg-ink text-white transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 lg:transition-none"
       @keydown.escape.window="menuOpen = false">
    <div class="flex h-16 items-center justify-between px-5">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-leaf-300">
            <x-application-logo class="h-8 w-8 text-leaf-300" />
            <span class="font-display text-[1.05rem] font-semibold leading-tight">Predictive<br>Inventory</span>
        </a>
        <button type="button" @click="menuOpen = false"
                class="rounded-lg p-1.5 text-white/70 hover:bg-white/10 lg:hidden" aria-label="Menüyü kapat">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 18 18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <div class="mx-5 mb-4 rounded-lg bg-white/5 px-3 py-2">
        <p class="text-xs text-white/50">Şirket</p>
        <p class="truncate text-sm font-medium">{{ $user->company->name ?? '—' }}</p>
    </div>

    <nav class="flex-1 space-y-1 px-3" aria-label="Ana menü">
        @foreach ($menu as [$route, $pattern, $label, $icon])
            @php($active = request()->routeIs($pattern))
            <a href="{{ route($route) }}"
               @class([
                   'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-leaf-300',
                   'bg-leaf-500 text-white' => $active,
                   'text-white/70 hover:bg-white/10 hover:text-white' => ! $active,
               ])
               @if ($active) aria-current="page" @endif>
                <svg class="h-5 w-5 shrink-0 {{ $active ? 'text-white' : 'text-leaf-300/80 group-hover:text-leaf-200' }}"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="{{ $icon }}"/>
                </svg>
                {{ $label }}
            </a>
        @endforeach
    </nav>

    {{-- Kullanıcı --}}
    <div class="border-t border-white/10 p-3" x-data="{ userOpen: false }">
        <div x-show="userOpen" x-cloak x-transition.origin.bottom class="mb-2 space-y-1">
            <a href="{{ route('profile.edit') }}"
               class="block rounded-lg px-3 py-2 text-sm text-white/80 hover:bg-white/10 hover:text-white">Profil ve şifre</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-white/80 hover:bg-white/10 hover:text-white">
                    Çıkış yap
                </button>
            </form>
        </div>
        <button type="button" @click="userOpen = ! userOpen" :aria-expanded="userOpen"
                class="flex w-full items-center gap-3 rounded-lg px-2 py-2 text-left hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-leaf-300">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-leaf-300 font-display text-sm font-semibold text-ink">{{ $initials }}</span>
            <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-medium">{{ $user->name }}</span>
                <span class="block truncate text-xs text-white/50">{{ $user->email }}</span>
            </span>
            <svg class="h-4 w-4 shrink-0 text-white/50 transition" :class="userOpen && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.3 12.7a1 1 0 0 0 1.4 0L10 9.4l3.3 3.3a1 1 0 0 0 1.4-1.4l-4-4a1 1 0 0 0-1.4 0l-4 4a1 1 0 0 0 0 1.4Z" clip-rule="evenodd"/></svg>
        </button>
    </div>
</aside>
