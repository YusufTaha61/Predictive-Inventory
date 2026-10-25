<x-app-layout>
    <x-slot name="title">Ürünü düzenle</x-slot>
    <x-slot name="header">
        <x-page-header title="Ürünü düzenle" :subtitle="$product->name">
            <a href="{{ route('products.index') }}" class="btn-secondary">Ürünlere dön</a>
        </x-page-header>
    </x-slot>

    <div class="max-w-2xl">
        <div class="card card-body">
            <form method="POST" action="{{ route('products.update', $product) }}">
                @method('PUT')
                @include('products._form')
            </form>
        </div>
    </div>
</x-app-layout>
