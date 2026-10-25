@php
    $money = fn ($value) => number_format($value, 0, ',', '.') . ' ₺';
    $number = fn ($value) => number_format($value, 0, ',', '.');
    $maxDaily = max(array_map(fn ($d) => $d['sale'] + $d['waste'], $daily)) ?: 1;
    $dayCount = count($daily);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Panel</h2>
                <p class="text-sm text-gray-500">Son 30 gün: {{ $start->format('d.m.Y') }} – {{ $end->format('d.m.Y') }}</p>
            </div>
            <a href="{{ route('stock-movements.create') }}"
               class="inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                + Stok hareketi ekle
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if ($productCount === 0)
                <div class="bg-white shadow-sm sm:rounded-lg p-10 text-center">
                    <p class="text-lg font-semibold text-gray-800">Henüz ürün yok</p>
                    <p class="mt-1 text-gray-500">İlk ürününü ekle, sonra stok hareketlerini gir. Panel satış ve fireyi burada özetler.</p>
                    <a href="{{ route('products.create') }}" class="mt-4 inline-block rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white">İlk ürününü ekle</a>
                </div>
            @else
                {{-- Özet sayılar --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="bg-white shadow-sm sm:rounded-lg p-5">
                        <p class="flex items-center gap-2 text-sm text-gray-500"><span class="h-2 w-2 rounded-full bg-green-500"></span>Satış</p>
                        <p class="mt-1 text-3xl font-bold text-gray-800">{{ $number($totals['sale']) }}</p>
                        <p class="text-sm text-gray-500">Ciro {{ $money($totals['revenue']) }}</p>
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-5">
                        <p class="flex items-center gap-2 text-sm text-gray-500"><span class="h-2 w-2 rounded-full bg-red-500"></span>Fire</p>
                        <p class="mt-1 text-3xl font-bold text-gray-800">{{ $number($totals['waste']) }}</p>
                        <p class="text-sm text-gray-500">Maliyeti {{ $money($totals['waste_cost']) }}</p>
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-5">
                        <p class="text-sm text-gray-500">Fire oranı</p>
                        <p class="mt-1 text-3xl font-bold text-gray-800">%{{ number_format($totals['waste_rate'], 1, ',', '.') }}</p>
                        <p class="text-sm text-gray-500">Satılan ve fire olanların içinde</p>
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-5">
                        <p class="text-sm text-gray-500">Stoğu azalan ürün</p>
                        <p class="mt-1 text-3xl font-bold text-gray-800">{{ $lowStock->count() }} <span class="text-lg font-medium text-gray-400">/ {{ $productCount }}</span></p>
                        <p class="text-sm text-gray-500">Yakında bitecek stok</p>
                    </div>
                </div>

                {{-- Günlük satış ve fire grafiği --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="font-semibold text-gray-800">Günlük satış ve fire</h3>
                        <div class="flex gap-4 text-sm text-gray-500">
                            <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-green-500"></span>Satış</span>
                            <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-red-500"></span>Fire</span>
                        </div>
                    </div>

                    <div class="relative mt-6">
                        <div class="pointer-events-none absolute inset-0 flex flex-col justify-between" aria-hidden="true">
                            @foreach ([1, 0.5, 0] as $level)
                                <div class="flex items-center gap-2">
                                    <span class="w-10 shrink-0 text-right text-xs text-gray-400">{{ $number($maxDaily * $level) }}</span>
                                    <span class="h-px flex-1 bg-gray-200"></span>
                                </div>
                            @endforeach
                        </div>

                        <div class="relative ml-12 flex items-end gap-[3px]" style="height: 200px">
                            @foreach ($daily as $date => $day)
                                <div class="flex h-full flex-1 flex-col justify-end"
                                     title="{{ \Illuminate\Support\Carbon::parse($date)->format('d.m.Y') }}: satış {{ $number($day['sale']) }}, fire {{ $number($day['waste']) }}">
                                    <div class="rounded-t-[3px] bg-red-500" style="height: {{ 100 * $day['waste'] / $maxDaily }}%"></div>
                                    <div class="bg-green-500 hover:bg-green-600" style="height: {{ 100 * $day['sale'] / $maxDaily }}%"></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="ml-12 mt-2 flex justify-between text-xs text-gray-400">
                        @foreach (array_keys($daily) as $i => $date)
                            @if ($i % 7 === 0 || $i === $dayCount - 1)
                                <span>{{ \Illuminate\Support\Carbon::parse($date)->format('d.m') }}</span>
                            @endif
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                    {{-- En çok fire veren ürünler --}}
                    <div class="bg-white shadow-sm sm:rounded-lg">
                        <h3 class="p-6 pb-3 font-semibold text-gray-800">En çok fire veren ürünler</h3>
                        @if ($topWaste->isEmpty())
                            <p class="px-6 pb-6 text-sm text-gray-500">Bu dönemde fire yok.</p>
                        @else
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-gray-500">
                                    <tr>
                                        <th class="px-6 py-2 text-left font-medium">Ürün</th>
                                        <th class="px-6 py-2 text-right font-medium">Fire</th>
                                        <th class="px-6 py-2 text-right font-medium">Oran</th>
                                        <th class="px-6 py-2 text-right font-medium">Maliyet</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($topWaste as $item)
                                        <tr>
                                            <td class="px-6 py-2">
                                                <a href="{{ route('products.forecast', $item['product']) }}" class="font-medium hover:underline">{{ $item['product']->name }}</a>
                                            </td>
                                            <td class="px-6 py-2 text-right">{{ $number($item['waste']) }}</td>
                                            <td class="px-6 py-2 text-right">%{{ number_format($item['rate'], 1, ',', '.') }}</td>
                                            <td class="px-6 py-2 text-right font-medium">{{ $money($item['cost']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>

                    {{-- Stoğu azalan ürünler --}}
                    <div class="bg-white shadow-sm sm:rounded-lg">
                        <h3 class="p-6 pb-3 font-semibold text-gray-800">Stoğu azalan ürünler</h3>
                        @if ($lowStock->isEmpty())
                            <p class="px-6 pb-6 text-sm text-gray-500">Yakında bitecek stok yok.</p>
                        @else
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-gray-500">
                                    <tr>
                                        <th class="px-6 py-2 text-left font-medium">Ürün</th>
                                        <th class="px-6 py-2 text-right font-medium">Stok</th>
                                        <th class="px-6 py-2 text-left font-medium">Kaç gün yeter</th>
                                        <th class="px-6 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($lowStock as $item)
                                        <tr>
                                            <td class="px-6 py-2 font-medium">{{ $item['product']->name }}</td>
                                            <td class="px-6 py-2 text-right">{{ $number($item['stock']) }} {{ $item['product']->unit }}</td>
                                            <td class="px-6 py-2">
                                                @if ($item['days_left'] < 1)
                                                    <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">1 günden az</span>
                                                @else
                                                    <span class="rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800">{{ number_format($item['days_left'], 1, ',', '.') }} gün</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-2 text-right">
                                                <a href="{{ route('products.forecast', $item['product']) }}" class="whitespace-nowrap text-indigo-600 hover:underline">Sipariş önerisi</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <p class="border-t border-gray-100 px-6 py-4 text-sm text-gray-500">
                                Son 14 günün ortalama satışına göre hesaplandı. Bozulabilir ürünlerde {{ $lowStockDaysPerishable }} günden,
                                diğerlerinde {{ $lowStockDays }} günden az yetecek stok listelenir.
                            </p>
                        @endif
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>