@php
    $methodLabels = [
        'ml' => 'Makine öğrenmesi modeli',
        'seasonal_average' => 'Son 4 haftanın ortalaması',
        'history_average' => 'Geçmiş günlerin ortalaması',
        'no_data' => 'Veri yok',
    ];
    $riskLabels = [
        'none' => ['Yok', 'bg-green-100 text-green-800'],
        'low' => ['Düşük', 'bg-green-100 text-green-800'],
        'medium' => ['Orta', 'bg-yellow-100 text-yellow-800'],
        'high' => ['Yüksek', 'bg-red-100 text-red-800'],
    ];
    $days = $forecast['forecast'] ?? [];
    $maxUnits = collect($days)->max('units') ?: 1;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $product->name }} — Tahmin
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <a href="{{ route('products.index') }}" class="text-sm text-gray-600 hover:underline">← Ürünlere dön</a>

            @if (isset($forecast['error']))
                <div class="p-4 bg-red-100 text-red-800 rounded">{{ $forecast['error'] }}</div>
            @else
                @foreach ($forecast['warnings'] ?? [] as $warning)
                    <div class="p-3 bg-yellow-100 text-yellow-800 rounded text-sm">{{ $warning }}</div>
                @endforeach

                {{-- Özet kartları --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white p-5 rounded-lg shadow-sm">
                        <div class="text-sm text-gray-500">Mevcut stok</div>
                        <div class="text-2xl font-semibold">{{ $stock }} {{ $product->unit }}</div>
                    </div>

                    <div class="bg-white p-5 rounded-lg shadow-sm">
                        <div class="text-sm text-gray-500">Önerilen sipariş</div>
                        @if (isset($order['suggested_order']))
                            <div class="text-2xl font-semibold">{{ $order['suggested_order'] }} {{ $product->unit }}</div>
                            <div class="text-xs text-gray-500 mt-1">7 günlük satış + %20 yedek − stok</div>
                        @else
                            <div class="text-gray-400">{{ $order['error'] ?? 'Hesaplanamadı' }}</div>
                        @endif
                    </div>

                    <div class="bg-white p-5 rounded-lg shadow-sm">
                        <div class="text-sm text-gray-500">Fire riski</div>
                        @if ($waste === null)
                            <div class="text-gray-400 text-sm">Raf ömrü girilmemiş</div>
                        @elseif (isset($waste['risk_level']))
                            @php [$label, $color] = $riskLabels[$waste['risk_level']]; @endphp
                            <span class="inline-block px-2 py-1 rounded text-sm font-semibold {{ $color }}">{{ $label }}</span>
                            <div class="text-xs text-gray-500 mt-1">
                                {{ $waste['units_at_risk'] }} {{ $product->unit }} bozulmadan satılamayabilir
                            </div>
                        @else
                            <div class="text-gray-400">{{ $waste['error'] ?? 'Hesaplanamadı' }}</div>
                        @endif
                    </div>
                </div>

                {{-- 7 günlük tahmin --}}
                <div class="bg-white p-5 rounded-lg shadow-sm">
                    <div class="flex justify-between items-baseline mb-4">
                        <h3 class="font-semibold">Önümüzdeki 7 gün</h3>
                        <span class="text-xs text-gray-500">Yöntem: {{ $methodLabels[$forecast['method']] ?? $forecast['method'] }}</span>
                    </div>

                    @forelse ($days as $day)
                        <div class="flex items-center gap-3 py-1">
                            <div class="w-24 text-sm text-gray-600">{{ \Carbon\Carbon::parse($day['date'])->format('d.m.Y') }}</div>
                            <div class="flex-1 bg-gray-100 rounded h-5">
                                <div class="bg-indigo-500 h-5 rounded" style="width: {{ $day['units'] / $maxUnits * 100 }}%"></div>
                            </div>
                            <div class="w-16 text-right text-sm">{{ $day['units'] }}</div>
                        </div>
                    @empty
                        <div class="text-gray-400 text-sm">Tahmin yok</div>
                    @endforelse

                    @if ($forecast['total_next_7_days'] !== null)
                        <div class="mt-4 pt-3 border-t text-sm">
                            Toplam: <strong>{{ $forecast['total_next_7_days'] }} {{ $product->unit }}</strong>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>