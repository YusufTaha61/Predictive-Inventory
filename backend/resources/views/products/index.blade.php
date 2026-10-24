<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ürünler</h2>
            <a href="{{ route('products.create') }}">
                <x-primary-button type="button">Yeni Ürün</x-primary-button>
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
                    @if ($products->isEmpty())
                        <p>Henüz ürün yok. "Yeni Ürün" ile ilk ürününü ekle.</p>
                    @else
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b">
                                    <th class="py-2">Ad</th>
                                    <th class="py-2">Stok Kodu</th>
                                    <th class="py-2">Birim</th>
                                    <th class="py-2">Mevcut Stok</th>
                                    <th class="py-2">Fiyat</th>
                                    <th class="py-2">Raf Ömrü</th>
                                    <th class="py-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($products as $product)
                                    <tr class="border-b">
                                        <td class="py-2">{{ $product->name }}</td>
                                        <td class="py-2">{{ $product->sku ?? '-' }}</td>
                                        <td class="py-2">{{ ucfirst($product->unit) }}</td>
                                        @php
                                            // Mevcut stok = girişler - (satışlar + fireler)
                                            $stock = (float) $product->stock_in - (float) $product->stock_out;
                                        @endphp
                                        <td class="py-2 {{ $stock <= 0 ? 'text-red-600 font-semibold' : '' }}">
                                            {{ rtrim(rtrim(number_format($stock, 2, ',', '.'), '0'), ',') }} {{ ucfirst($product->unit) }}
                                        </td>
                                        <td class="py-2">{{ number_format($product->price, 2, ',', '.') }} ₺</td>
                                        <td class="py-2">{{ $product->shelf_life_days ? $product->shelf_life_days.' gün' : '-' }}</td>
                                        <td class="py-2 text-right">
                                            <a href="{{ route('products.forecast', $product) }}" class="text-indigo-600 hover:underline mr-3">Tahmin</a>
                                            <a href="{{ route('products.edit', $product) }}" class="text-indigo-600 hover:underline">Düzenle</a>
                                            <form method="POST" action="{{ route('products.destroy', $product) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="ms-3 text-red-600 hover:underline">Sil</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="mt-4">{{ $products->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
