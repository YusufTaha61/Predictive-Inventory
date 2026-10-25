@php
    $statusLabels = [
        'active' => ['Devam ediyor', 'bg-green-100 text-green-800'],
        'upcoming' => ['Yaklaşan', 'bg-yellow-100 text-yellow-800'],
        'past' => ['Bitti', 'bg-gray-100 text-gray-600'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kampanyalar</h2>
                <p class="text-sm text-gray-500">İndirim günlerini gir. Tahmin bu günlerde satışın artacağını hesaba katar.</p>
            </div>
            <a href="{{ route('promotions.create') }}">
                <x-primary-button type="button">Yeni Kampanya</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">{{ session('status') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($promotions->isEmpty())
                        <p>Henüz kampanya yok. İndirim yaptığın günleri girersen satış tahmini o günleri dikkate alır.</p>
                    @else
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b">
                                    <th class="py-2">Ürün</th>
                                    <th class="py-2">Kampanya</th>
                                    <th class="py-2">Tarihler</th>
                                    <th class="py-2">İndirim</th>
                                    <th class="py-2">Durum</th>
                                    <th class="py-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($promotions as $promotion)
                                    @php($status = $statusLabels[$promotion->status()])
                                    <tr class="border-b">
                                        <td class="py-2 font-medium">{{ $promotion->product->name }}</td>
                                        <td class="py-2">{{ $promotion->name }}</td>
                                        <td class="py-2 whitespace-nowrap">
                                            {{ $promotion->starts_on->format('d.m.Y') }} – {{ $promotion->ends_on->format('d.m.Y') }}
                                            <span class="block text-xs text-gray-500">{{ $promotion->days() }} gün</span>
                                        </td>
                                        <td class="py-2 whitespace-nowrap">
                                            @if ($promotion->discount_percent)
                                                %{{ rtrim(rtrim(number_format($promotion->discount_percent, 2, ',', '.'), '0'), ',') }}
                                                <span class="block text-xs text-gray-500">{{ number_format($promotion->priceFor($promotion->product), 2, ',', '.') }} ₺</span>
                                            @else
                                                <span class="text-gray-500">Fiyat aynı</span>
                                            @endif
                                        </td>
                                        <td class="py-2">
                                            <span class="px-2 py-1 rounded text-xs {{ $status[1] }}">{{ $status[0] }}</span>
                                        </td>
                                        <td class="py-2">
                                            <div class="flex justify-end">
                                                <form method="POST" action="{{ route('promotions.destroy', $promotion) }}"
                                                      onsubmit="return confirm('Bu kampanyayı silmek istediğine emin misin?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="px-3 py-1 rounded-md border border-red-200 bg-red-50 text-red-700 text-xs font-medium hover:bg-red-100">Sil</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="mt-4">{{ $promotions->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
