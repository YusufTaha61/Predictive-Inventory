<x-app-layout>
    <x-slot name="title">Yeni ürün</x-slot>
    <x-slot name="header">
        <x-page-header title="Yeni ürün" subtitle="Ürünün bilgilerini gir. Stoğu sonra Stok Hareketleri sayfasından eklersin.">
            <a href="{{ route('products.index') }}" class="btn-secondary">Ürünlere dön</a>
        </x-page-header>
    </x-slot>

    <div class="max-w-2xl">
        <div class="card card-body">
            <form method="POST" action="{{ route('products.store') }}">
                @include('products._form')
            </form>
        </div>
    </div>
</x-app-layout>
