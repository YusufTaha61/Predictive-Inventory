@csrf

<div>
    <x-input-label for="name" value="Ürün Adı" />
    <x-text-input id="name" name="name" type="text" class="block mt-1 w-full" :value="old('name', $product->name ?? '')" required autofocus />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="sku" value="Stok Kodu (SKU, isteğe bağlı)" />
    <x-text-input id="sku" name="sku" type="text" class="block mt-1 w-full" :value="old('sku', $product->sku ?? '')" />
    <x-input-error :messages="$errors->get('sku')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="unit" value="Birim (adet, kg, litre...)" />
    <x-text-input id="unit" name="unit" type="text" class="block mt-1 w-full" :value="old('unit', $product->unit ?? 'adet')" required />
    <x-input-error :messages="$errors->get('unit')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="price" value="Fiyat (₺)" />
    <x-text-input id="price" name="price" type="number" step="0.01" min="0" class="block mt-1 w-full" :value="old('price', $product->price ?? '0')" required />
    <x-input-error :messages="$errors->get('price')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="shelf_life_days" value="Raf Ömrü (gün, isteğe bağlı)" />
    <x-text-input id="shelf_life_days" name="shelf_life_days" type="number" min="1" class="block mt-1 w-full" :value="old('shelf_life_days', $product->shelf_life_days ?? '')" />
    <x-input-error :messages="$errors->get('shelf_life_days')" class="mt-2" />
</div>

<div class="flex items-center justify-end mt-6">
    <a href="{{ route('products.index') }}" class="text-sm text-gray-600 hover:underline me-4">Vazgeç</a>
    <x-primary-button>Kaydet</x-primary-button>
</div>
