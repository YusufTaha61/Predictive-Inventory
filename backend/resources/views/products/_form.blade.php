@csrf

<div>
    <x-input-label for="name" value="Ürün Adı" />
    <x-text-input id="name" name="name" class="mt-1.5 block w-full"
                  :value="old('name', $product->name ?? '')" required autofocus />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div class="mt-5">
    <x-input-label for="sku" value="Stok Kodu (SKU, isteğe bağlı)" />
    <x-text-input id="sku" name="sku" class="mt-1.5 block w-full"
                  :value="old('sku', $product->sku ?? '')" placeholder="Örn: SUT-001" />
    <x-input-error :messages="$errors->get('sku')" class="mt-2" />
</div>

<div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="unit" value="Ölçü Birimi" />
        <select id="unit" name="unit" required class="field-input mt-1.5 block w-full">
            @foreach (\App\Models\Product::UNITS as $unit)
                <option value="{{ $unit }}" @selected(old('unit', $product->unit ?? 'adet') === $unit)>{{ ucfirst($unit) }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('unit')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="price" value="Satış Fiyatı (₺)" />
        <x-text-input id="price" name="price" type="number" step="any" min="0" class="mt-1.5 block w-full"
                      :value="old('price', $product->price ?? '')" required />
        <x-input-error :messages="$errors->get('price')" class="mt-2" />
    </div>
</div>

<div class="mt-5">
    <x-input-label for="shelf_life_days" value="Raf Ömrü (gün, isteğe bağlı)" />
    <x-text-input id="shelf_life_days" name="shelf_life_days" type="number" min="1" class="mt-1.5 block w-full"
                  :value="old('shelf_life_days', $product->shelf_life_days ?? '')" />
    <p class="field-hint">Ürün geldikten sonra kaç gün satılabilir? Fire riski bu bilgiyle hesaplanır.</p>
    <x-input-error :messages="$errors->get('shelf_life_days')" class="mt-2" />
</div>

@if (isset($product))
    {{-- Düzenleme: stok sadece gösterilir --}}
    <div class="mt-5">
        <x-input-label value="Mevcut Stok" />
        <p class="mt-1.5 font-display text-2xl font-semibold tabular-nums">
            {{ rtrim(rtrim(number_format($product->currentStock(), 2, ',', '.'), '0'), ',') }} {{ $product->unit }}
        </p>
        <p class="field-hint">
            Stok miktarı
            <a href="{{ route('stock-movements.create') }}" class="text-link">Stok Hareketleri</a>
            sayfasından değiştirilir.
        </p>
    </div>
@endif

<div class="mt-8 flex items-center gap-3 border-t border-line pt-6">
    <x-primary-button>Kaydet</x-primary-button>
    <a href="{{ route('products.index') }}" class="btn-secondary">İptal</a>
</div>
