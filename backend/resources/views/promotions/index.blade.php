@php
    $statusLabels = [
        'active' => ['Devam ediyor', 'badge-green'],
        'upcoming' => ['Yaklaşan', 'badge-yellow'],
        'past' => ['Bitti', 'badge-gray'],
    ];
@endphp

<x-app-layout>
    <x-slot name="title">Kampanyalar</x-slot>
    <x-slot name="header">
        <x-page-header title="Kampanyalar"
                       subtitle="Kampanya ve indirim günlerini gir. Tahmin bu günlerde satışın artacağını hesaba katar; geçmiş kampanyaları da girersen model etkisini daha iyi öğrenir.">
            <a href="{{ route('promotions.create') }}" class="btn-primary">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 4a1 1 0 0 1 1 1v4h4a1 1 0 1 1 0 2h-4v4a1 1 0 1 1-2 0v-4H5a1 1 0 1 1 0-2h4V5a1 1 0 0 1 1-1Z"/></svg>
                Yeni kampanya
            </a>
        </x-page-header>
    </x-slot>

    <div class="card">
        @if ($promotions->isEmpty())
            <x-empty-state title="Henüz kampanya girilmedi" text="İndirim yaptığın günleri girersen satış tahmini o günleri dikkate alır.">
                <a href="{{ route('promotions.create') }}" class="btn-primary">İlk kampanyanı ekle</a>
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ürün</th>
                            <th>Kampanya</th>
                            <th>Tarihler</th>
                            <th>İndirim</th>
                            <th>Durum</th>
                            <th class="num"><span class="sr-only">İşlem</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($promotions as $promotion)
                            @php($status = $statusLabels[$promotion->status()])
                            <tr>
                                <td class="font-medium">{{ $promotion->product->name }}</td>
                                <td>{{ $promotion->name }}</td>
                                <td class="whitespace-nowrap">
                                    <span class="tabular-nums">{{ $promotion->starts_on->format('d.m.Y') }} – {{ $promotion->ends_on->format('d.m.Y') }}</span>
                                    <span class="block text-xs text-ink-muted">{{ $promotion->days() }} gün</span>
                                </td>
                                <td class="whitespace-nowrap">
                                    @if ($promotion->discount_percent)
                                        <span class="font-medium">%{{ rtrim(rtrim(number_format($promotion->discount_percent, 2, ',', '.'), '0'), ',') }}</span>
                                        <span class="block text-xs text-ink-muted">{{ number_format($promotion->priceFor($promotion->product), 2, ',', '.') }} ₺</span>
                                    @else
                                        <span class="text-ink-muted">Fiyat aynı</span>
                                    @endif
                                </td>
                                <td><span class="{{ $status[1] }}">{{ $status[0] }}</span></td>
                                <td class="num">
                                    <form action="{{ route('promotions.destroy', $promotion) }}" method="POST"
                                          onsubmit="return confirm('Bu kampanyayı silmek istediğine emin misin?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="action-link-danger">Sil</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($promotions->hasPages())
                <div class="border-t border-line px-4 py-3">{{ $promotions->links() }}</div>
            @endif
        @endif
    </div>
</x-app-layout>
