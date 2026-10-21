<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Yeni Stok Hareketi</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if ($products->isEmpty())
                    <div class="p-6">
                        <p>Önce bir ürün eklemelisin.</p>
                        <a href="{{ route('products.create') }}" class="text-indigo-600 hover:underline">Ürün ekle</a>
                    </div>
                @else
                    <form method="POST" action="{{ route('stock-movements.store') }}" class="p-6">
                        @csrf

                        <div>
                            <x-input-label for="product_id" value="Ürün" />
                            <select id="product_id" name="product_id" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                                <option value="">Seç...</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->name }} ({{ ucfirst($product->unit) }})</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('product_id')" class="mt-2" />
                        </div>

                        <div class="mt-4">
                            <x-input-label for="type" value="Hareket Türü" />
                            <select id="type" name="type" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                                @foreach (\App\Models\StockMovement::TYPES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('type')" class="mt-2" />
                        </div>

                        <div class="mt-4">
                            <x-input-label for="quantity" value="Miktar" />
                            <x-text-input id="quantity" name="quantity" type="number" step="any" min="0" class="block mt-1 w-full" :value="old('quantity', 0)" required />
                            <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
                        </div>

                        <div class="mt-4">
                            <x-input-label for="moved_on" value="Tarih" />
                            <x-text-input id="moved_on" name="moved_on" type="date" class="block mt-1 w-full" :value="old('moved_on', now()->toDateString())" max="{{ now()->toDateString() }}" required />
                            <x-input-error :messages="$errors->get('moved_on')" class="mt-2" />
                        </div>

                        <div class="mt-4">
                            <x-input-label for="note" value="Not (isteğe bağlı)" />
                            <x-text-input id="note" name="note" type="text" class="block mt-1 w-full" :value="old('note')" />
                            <x-input-error :messages="$errors->get('note')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('stock-movements.index') }}" class="text-sm text-gray-600 hover:underline me-4">Vazgeç</a>
                            <x-primary-button>Kaydet</x-primary-button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
