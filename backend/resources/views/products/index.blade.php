@php
    // 1,50 → 1,5 ; 2,00 → 2
    $qty = fn ($value) => rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
@endphp

<x-app-layout>
    <x-slot name="title">Ürünler</x-slot>
    <x-slot name="header">
        <x-page-header title="Ürünler" subtitle="Ürünlerini ekle, düzenle ve her biri için satış tahminine bak.">
            <a href="{{ route('products.create') }}" class="btn-primary">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 4a1 1 0 0 1 1 1v4h4a1 1 0 1 1 0 2h-4v4a1 1 0 1 1-2 0v-4H5a1 1 0 1 1 0-2h4V5a1 1 0 0 1 1-1Z"/></svg>
                Yeni ürün
            </a>
        </x-page-header>
    </x-slot>

    <div class="card">
        @if ($products->isEmpty())
            <x-empty-state title="Henüz ürün eklenmedi" text="Ürün ekleyince stok hareketlerini girebilir ve satış tahmini alabilirsin.">
                <a href="{{ route('products.create') }}" class="btn-primary">İlk ürününü ekle</a>
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ürün</th>
                            <th>Birim</th>
                            <th class="num">Raf ömrü</th>
                            <th class="num">Fiyat</th>
                            <th class="num">Stok</th>
                            <th class="num"><span class="sr-only">İşlemler</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            @php
                                // Mevcut stok = girişler - (satışlar + fireler)
                                $stock = (float) $product->stock_in - (float) $product->stock_out;
                            @endphp
                            <tr>
                                <td>
                                    <p class="font-medium">{{ $product->name }}</p>
                                    <p class="text-xs text-ink-muted">{{ $product->sku ?? 'Stok kodu yok' }}</p>
                                </td>
                                <td><span class="badge-gray">{{ ucfirst($product->unit) }}</span></td>
                                <td class="num text-ink-soft">{{ $product->shelf_life_days ? $product->shelf_life_days.' gün' : '—' }}</td>
                                <td class="num">{{ number_format($product->price, 2, ',', '.') }} ₺</td>
                                <td class="num font-medium {{ $stock <= 0 ? 'text-tomato-600' : '' }}">{{ $qty($stock) }}</td>
                                <td class="num">
                                    <div class="flex items-center justify-end gap-4 whitespace-nowrap">
                                        <a href="{{ route('products.forecast', $product) }}" class="btn-secondary px-3 py-1.5 text-xs">Tahmin</a>
                                        <a href="{{ route('products.edit', $product) }}" class="action-link">Düzenle</a>
                                        <form action="{{ route('products.destroy', $product) }}" method="POST"
                                              onsubmit="return confirm('Bu ürünü silmek istediğine emin misin?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="action-link-danger">Sil</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($products->hasPages())
                <div class="border-t border-line px-4 py-3">{{ $products->links() }}</div>
            @endif
        @endif
    </div>
</x-app-layout>
