<x-app-layout>
    <x-slot name="title">Yeni stok hareketi</x-slot>
    <x-slot name="header">
        <x-page-header title="Yeni stok hareketi" subtitle="Gelen malı, satışı ya da fireyi kaydet. Ürünün stoğu otomatik güncellenir.">
            <a href="{{ route('stock-movements.index') }}" class="btn-secondary">Stok hareketlerine dön</a>
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
                <form method="POST" action="{{ route('stock-movements.store') }}">
                    @csrf

                    <div>
                        <x-input-label for="product_id" value="Ürün" />
                        <select id="product_id" name="product_id" required class="field-input mt-1.5 block w-full">
                            <option value="">Seçiniz</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>
                                    {{ $product->name }} ({{ ucfirst($product->unit) }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('product_id')" class="mt-2" />
                    </div>

                    <div class="mt-5">
                        <x-input-label for="type" value="Hareket Türü" />
                        <select id="type" name="type" required class="field-input mt-1.5 block w-full">
                            @foreach (\App\Models\StockMovement::TYPES as $value => $label)
                                <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('type')" class="mt-2" />
                    </div>

                    <div class="mt-5">
                        <x-input-label for="quantity" value="Miktar" />
                        <x-text-input id="quantity" name="quantity" type="number" step="any" min="0" class="mt-1.5 block w-full"
                                      :value="old('quantity')" required />
                        <p class="field-hint">Kg ya da litre gibi birimlerde ondalıklı yazabilirsin (ör. 1,5).</p>
                        <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
                    </div>

                    <div class="mt-5">
                        <x-input-label for="moved_on" value="Tarih" />
                        <x-text-input id="moved_on" name="moved_on" type="date" class="mt-1.5 block w-full"
                                      :value="old('moved_on', now()->toDateString())" max="{{ now()->toDateString() }}" required />
                        <x-input-error :messages="$errors->get('moved_on')" class="mt-2" />
                    </div>

                    <div class="mt-5">
                        <x-input-label for="note" value="Not (isteğe bağlı)" />
                        <x-text-input id="note" name="note" class="mt-1.5 block w-full" :value="old('note')" />
                        <x-input-error :messages="$errors->get('note')" class="mt-2" />
                    </div>

                    <div class="mt-8 flex items-center gap-3 border-t border-line pt-6">
                        <x-primary-button>Kaydet</x-primary-button>
                        <a href="{{ route('stock-movements.index') }}" class="btn-secondary">İptal</a>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
