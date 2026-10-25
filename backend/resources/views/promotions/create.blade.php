<x-app-layout>
    <x-slot name="title">Yeni kampanya</x-slot>
    <x-slot name="header">
        <x-page-header title="Yeni kampanya" subtitle="Kampanya günlerini gir; tahmin o günlerde satışın artacağını hesaba katar.">
            <a href="{{ route('promotions.index') }}" class="btn-secondary">Kampanyalara dön</a>
        </x-page-header>
    </x-slot>

    <div class="max-w-2xl">
        <div class="card card-body">
                @if ($products->isEmpty())
                    <p class="text-ink-soft">
                        Önce bir ürün eklemelisin.
                        <a href="{{ route('products.create') }}" class="text-link">Ürün ekle</a>
                    </p>
                @else
                    <form method="POST" action="{{ route('promotions.store') }}">
                        @csrf

                        <div>
                            <x-input-label for="product_id" value="Ürün" />
                            <select id="product_id" name="product_id" required
                                    class="field-input mt-1.5 block w-full">
                                <option value="">Seçiniz</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" @selected(old('product_id', $selected) == $product->id)>
                                        {{ $product->name }} ({{ number_format($product->price, 2, ',', '.') }} ₺)
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('product_id')" class="mt-2" />
                        </div>

                        <div class="mt-5">
                            <x-input-label for="name" value="Kampanya adı" />
                            <x-text-input id="name" name="name" class="mt-1.5 block w-full"
                                          :value="old('name')" placeholder="Örn: Hafta sonu indirimi" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="starts_on" value="Başlangıç günü" />
                                <x-text-input id="starts_on" name="starts_on" type="date" class="mt-1.5 block w-full"
                                              :value="old('starts_on', now()->toDateString())" required />
                                <x-input-error :messages="$errors->get('starts_on')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="ends_on" value="Bitiş günü (dahil)" />
                                <x-text-input id="ends_on" name="ends_on" type="date" class="mt-1.5 block w-full"
                                              :value="old('ends_on', now()->addDays(3)->toDateString())" required />
                                <x-input-error :messages="$errors->get('ends_on')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-5">
                            <x-input-label for="discount_percent" value="İndirim oranı % (isteğe bağlı)" />
                            <x-text-input id="discount_percent" name="discount_percent" type="number" min="1" max="90" step="0.5"
                                          class="mt-1.5 block w-full" :value="old('discount_percent')" placeholder="Örn: 20" />
                            <p class="field-hint">Boş bırakırsan fiyat değişmez, sadece kampanya günü olarak işaretlenir.</p>
                            <x-input-error :messages="$errors->get('discount_percent')" class="mt-2" />
                        </div>

                        <div class="mt-8 flex items-center gap-3 border-t border-line pt-6">
                            <x-primary-button>Kaydet</x-primary-button>
                            <a href="{{ route('promotions.index') }}" class="btn-secondary">İptal</a>
                        </div>
                    </form>
                @endif
        </div>
    </div>
</x-app-layout>
