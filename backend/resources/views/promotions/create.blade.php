<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Yeni Kampanya</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if ($products->isEmpty())
                    <p class="text-gray-600">
                        Önce bir ürün eklemelisin.
                        <a href="{{ route('products.create') }}" class="text-indigo-600 hover:underline">Ürün ekle</a>
                    </p>
                @else
                    <form method="POST" action="{{ route('promotions.store') }}">
                        @csrf

                        <div>
                            <x-input-label for="product_id" value="Ürün" />
                            <select id="product_id" name="product_id" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Seçiniz</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" @selected(old('product_id', $selected) == $product->id)>
                                        {{ $product->name }} ({{ number_format($product->price, 2, ',', '.') }} ₺)
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('product_id')" class="mt-2" />
                        </div>

                        <div class="mt-4">
                            <x-input-label for="name" value="Kampanya adı" />
                            <x-text-input id="name" name="name" class="mt-1 block w-full"
                                          :value="old('name')" placeholder="Örn: Hafta sonu indirimi" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="starts_on" value="Başlangıç günü" />
                                <x-text-input id="starts_on" name="starts_on" type="date" class="mt-1 block w-full"
                                              :value="old('starts_on', now()->toDateString())" required />
                                <x-input-error :messages="$errors->get('starts_on')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="ends_on" value="Bitiş günü (dahil)" />
                                <x-text-input id="ends_on" name="ends_on" type="date" class="mt-1 block w-full"
                                              :value="old('ends_on', now()->addDays(3)->toDateString())" required />
                                <x-input-error :messages="$errors->get('ends_on')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-4">
                            <x-input-label for="discount_percent" value="İndirim oranı % (isteğe bağlı)" />
                            <x-text-input id="discount_percent" name="discount_percent" type="number" min="1" max="90" step="0.5"
                                          class="mt-1 block w-full" :value="old('discount_percent')" placeholder="Örn: 20" />
                            <p class="mt-1 text-sm text-gray-500">Boş bırakırsan fiyat değişmez, sadece kampanya günü olarak işaretlenir.</p>
                            <x-input-error :messages="$errors->get('discount_percent')" class="mt-2" />
                        </div>

                        <div class="mt-6 flex items-center gap-3">
                            <x-primary-button>Kaydet</x-primary-button>
                            <a href="{{ route('promotions.index') }}">
                                <x-secondary-button type="button">İptal</x-secondary-button>
                            </a>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
