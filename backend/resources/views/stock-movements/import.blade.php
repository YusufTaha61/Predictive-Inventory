@php
    $sampleRows = [
        ['15.10.2026', 'FRN-001', 'Satış', '40', ''],
        ['15.10.2026', 'Süt 1 L', 'Giriş', '24', 'Tedarikçi'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dosyadan stok hareketi yükle</h2>
                <p class="text-sm text-gray-500">Çok sayıda hareketi Excel'den hazırladığın bir CSV dosyasıyla tek seferde gir.</p>
            </div>
            <a href="{{ route('stock-movements.index') }}">
                <x-secondary-button type="button">Stok hareketlerine dön</x-secondary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('import_errors'))
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800" role="alert">
                    <p class="font-medium">Dosya yüklenmedi, hiçbir satır kaydedilmedi. Şu hataları düzeltip tekrar dene:</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach (session('import_errors') as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
                {{-- Dosya seçme formu --}}
                <div class="h-fit bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800">Dosyanı seç</h3>
                    <form action="{{ route('stock-movements.import.store') }}" method="POST" enctype="multipart/form-data" class="mt-4">
                        @csrf
                        <label for="file"
                               class="flex cursor-pointer flex-col items-center rounded-lg border-2 border-dashed border-gray-300 px-4 py-8 text-center hover:border-gray-400 hover:bg-gray-50"
                               x-data="{ name: '' }">
                            <span class="text-3xl" aria-hidden="true">📄</span>
                            <span class="mt-3 text-sm font-medium text-gray-800" x-text="name || 'CSV dosyası seç'">CSV dosyası seç</span>
                            <span class="mt-1 text-xs text-gray-500">.csv ya da .txt</span>
                            <input id="file" name="file" type="file" accept=".csv,.txt" required class="sr-only"
                                   @change="name = $event.target.files[0]?.name || ''" />
                        </label>
                        <x-input-error :messages="$errors->get('file')" class="mt-2" />

                        <div class="mt-6 flex flex-wrap items-center gap-3">
                            <x-primary-button>Yükle</x-primary-button>
                            <a href="{{ route('stock-movements.import.template') }}">
                                <x-secondary-button type="button">Örnek dosyayı indir</x-secondary-button>
                            </a>
                        </div>
                    </form>
                </div>

                {{-- Açıklama --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4 text-sm text-gray-600">
                    <h3 class="font-semibold text-gray-800">Dosya nasıl olmalı?</h3>
                    <p>İlk satır başlık olmalı. Excel'de hazırlayıp <strong>Farklı Kaydet → CSV</strong> ile kaydedebilirsin.</p>

                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-gray-500">
                                <tr>
                                    <th class="px-4 py-2 text-left font-medium">Tarih</th>
                                    <th class="px-4 py-2 text-left font-medium">Ürün</th>
                                    <th class="px-4 py-2 text-left font-medium">Tür</th>
                                    <th class="px-4 py-2 text-right font-medium">Miktar</th>
                                    <th class="px-4 py-2 text-left font-medium">Not</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($sampleRows as [$date, $product, $type, $qty, $note])
                                    <tr>
                                        <td class="px-4 py-2">{{ $date }}</td>
                                        <td class="px-4 py-2">{{ $product }}</td>
                                        <td class="px-4 py-2">{{ $type }}</td>
                                        <td class="px-4 py-2 text-right">{{ $qty }}</td>
                                        <td class="px-4 py-2">{{ $note }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <ul class="list-disc space-y-1.5 ps-5">
                        <li><strong>Ürün:</strong> stok kodu (SKU) ya da ürünün tam adı.</li>
                        <li><strong>Tür:</strong> Giriş, Satış ya da Fire.</li>
                        <li><strong>Miktar:</strong> pozitif sayı (1,5 gibi ondalıklı olabilir). <strong>Not</strong> sütunu boş kalabilir.</li>
                        <li>Bir seferde en fazla {{ number_format(\App\Services\MovementCsvImporter::MAX_ROWS, 0, ',', '.') }} satır yüklenebilir.</li>
                        <li>Dosyada tek bir hatalı satır bile varsa hiçbir satır kaydedilmez, hatalar satır numarasıyla gösterilir.</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>