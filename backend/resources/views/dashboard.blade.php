@php
    $money = fn ($value) => number_format($value, 0, ',', '.') . ' ₺';
    $number = fn ($value) => number_format($value, 0, ',', '.');
    $maxDaily = max(array_map(fn ($d) => $d['sale'] + $d['waste'], $daily)) ?: 1;
    $dayCount = count($daily);
@endphp

<x-app-layout>
    <x-slot name="title">Panel</x-slot>
    <x-slot name="header">
        <x-page-header title="Panel" :subtitle="'Son 30 gün: ' . $start->format('d.m.Y') . ' – ' . $end->format('d.m.Y')">
            <a href="{{ route('stock-movements.create') }}" class="btn-primary">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 4a1 1 0 0 1 1 1v4h4a1 1 0 1 1 0 2h-4v4a1 1 0 1 1-2 0v-4H5a1 1 0 1 1 0-2h4V5a1 1 0 0 1 1-1Z"/></svg>
                Stok hareketi ekle
            </a>
        </x-page-header>
    </x-slot>

    @if ($productCount === 0)
        <div class="card">
            <x-empty-state title="Henüz ürün yok" text="İlk ürününü ekle, sonra stok hareketlerini gir. Panel satış ve fireyi burada özetler.">
                <a href="{{ route('products.create') }}" class="btn-primary">İlk ürününü ekle</a>
            </x-empty-state>
        </div>
    @else
        {{-- Özet sayılar: tek kutuda dört bölme --}}
        <div class="card grid grid-cols-1 divide-y divide-line sm:grid-cols-2 sm:divide-y-0 lg:grid-cols-4 lg:divide-x">
            <div class="p-5 sm:p-6">
                <p class="stat-label flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-leaf-500"></span>Satış</p>
                <p class="stat-value">{{ $number($totals['sale']) }} <span class="text-lg font-medium text-ink-muted">adet</span></p>
                <p class="stat-note">Ciro {{ $money($totals['revenue']) }}</p>
            </div>
            <div class="p-5 sm:border-l sm:border-line sm:p-6 lg:border-l-0">
                <p class="stat-label flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-tomato-500"></span>Fire</p>
                <p class="stat-value">{{ $number($totals['waste']) }} <span class="text-lg font-medium text-ink-muted">adet</span></p>
                <p class="stat-note">Maliyeti {{ $money($totals['waste_cost']) }}</p>
            </div>
            <div class="p-5 sm:border-t sm:border-line sm:p-6 lg:border-t-0">
                <p class="stat-label">Fire oranı</p>
                <p class="stat-value">%{{ number_format($totals['waste_rate'], 1, ',', '.') }}</p>
                <p class="stat-note">Satılan ve fire olan ürünlerin içinde</p>
            </div>
            <div class="p-5 sm:border-l sm:border-t sm:border-line sm:p-6 lg:border-t-0">
                <p class="stat-label">Stoğu azalan ürün</p>
                <p class="stat-value">{{ $lowStock->count() }} <span class="text-lg font-medium text-ink-muted">/ {{ $productCount }}</span></p>
                <p class="stat-note">Yakında bitecek stok</p>
            </div>
        </div>

        {{-- Günlük satış ve fire grafiği --}}
        <div class="card card-body">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="card-title">Günlük satış ve fire</h2>
                <div class="flex gap-4 text-sm text-ink-soft">
                    <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-leaf-500"></span>Satış</span>
                    <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-tomato-500"></span>Fire</span>
                </div>
            </div>

            <div class="relative mt-6">
                {{-- Yatay kılavuz çizgileri --}}
                <div class="pointer-events-none absolute inset-0 flex flex-col justify-between" aria-hidden="true">
                    @foreach ([1, 0.5, 0] as $level)
                        <div class="flex items-center gap-2">
                            <span class="w-10 shrink-0 text-right text-xs tabular-nums text-ink-muted">{{ $number($maxDaily * $level) }}</span>
                            <span class="h-px flex-1 {{ $level === 0 ? 'bg-line' : 'bg-line/60' }}"></span>
                        </div>
                    @endforeach
                </div>

                <div class="relative ml-12 flex items-end gap-[3px]" style="height: 200px">
                    @foreach ($daily as $date => $day)
                        @php($label = \Illuminate\Support\Carbon::parse($date)->format('d.m.Y'))
                        <div class="group relative flex h-full flex-1 flex-col justify-end"
                             title="{{ $label }}: satış {{ $day['sale'] }}, fire {{ $day['waste'] }}">
                            <div class="rounded-t-[3px] bg-tomato-500" style="height: {{ 100 * $day['waste'] / $maxDaily }}%"></div>
                            <div class="bg-leaf-500 group-hover:bg-leaf-600 {{ $day['waste'] ? '' : 'rounded-t-[3px]' }}" style="height: {{ 100 * $day['sale'] / $maxDaily }}%"></div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="ml-12 mt-2 flex justify-between text-xs text-ink-muted">
                @foreach (array_keys($daily) as $i => $date)
                    @if ($i % 7 === 0 || $i === $dayCount - 1)
                        <span>{{ \Illuminate\Support\Carbon::parse($date)->format('d.m') }}</span>
                    @endif
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            {{-- En çok fire veren ürünler --}}
            <div class="card">
                <div class="card-body pb-3">
                    <h2 class="card-title">En çok fire veren ürünler</h2>
                </div>
                @if ($topWaste->isEmpty())
                    <p class="px-6 pb-6 text-sm text-ink-muted">Bu dönemde fire yok.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Ürün</th>
                                    <th class="num">Fire</th>
                                    <th class="num">Oran</th>
                                    <th class="num">Maliyet</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($topWaste as $item)
                                    <tr>
                                        <td>
                                            <a href="{{ route('products.forecast', $item['product']) }}" class="font-medium hover:text-leaf-600 hover:underline">{{ $item['product']->name }}</a>
                                        </td>
                                        <td class="num">{{ $number($item['waste']) }}</td>
                                        <td class="num">
                                            %{{ number_format($item['rate'], 1, ',', '.') }}
                                        </td>
                                        <td class="num font-medium">{{ $money($item['cost']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Stoğu azalan ürünler --}}
            <div class="card">
                <div class="card-body pb-3">
                    <h2 class="card-title">Stoğu azalan ürünler</h2>
                </div>
                @if ($lowStock->isEmpty())
                    <p class="px-6 pb-6 text-sm text-ink-muted">Yakında bitecek stok yok.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Ürün</th>
                                    <th class="num">Stok</th>
                                    <th>Kaç gün yeter</th>
                                    <th class="num"><span class="sr-only">İşlem</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lowStock as $item)
                                    <tr>
                                        <td class="font-medium">{{ $item['product']->name }}</td>
                                        <td class="num">{{ $number($item['stock']) }} {{ $item['product']->unit }}</td>
                                        <td>
                                            @if ($item['days_left'] < 1)
                                                <span class="badge-red">1 günden az</span>
                                            @else
                                                <span class="badge-yellow">{{ number_format($item['days_left'], 1, ',', '.') }} gün</span>
                                            @endif
                                        </td>
                                        <td class="num">
                                            <a href="{{ route('products.forecast', $item['product']) }}" class="action-link whitespace-nowrap">Sipariş önerisi</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="border-t border-line px-6 py-4 text-sm text-ink-muted">
                        Son 14 günün ortalama satışına göre hesaplandı. Bozulabilir ürünlerde {{ $lowStockDaysPerishable }} günden,
                        diğerlerinde {{ $lowStockDays }} günden az yetecek stok listelenir.
                    </p>
                @endif
            </div>
        </div>
    @endif
</x-app-layout>
