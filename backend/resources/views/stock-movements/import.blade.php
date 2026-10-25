@php
    $sampleRows = [
        ['15.10.2026', 'FRN-001', 'Satış', '40', ''],
        ['15.10.2026', 'Süt 1 L', 'Giriş', '24', 'Tedarikçi'],
    ];
@endphp

<x-app-layout>
    <x-slot name="title">Dosyadan yükle</x-slot>
    <x-slot name="header">
        <x-page-header title="Dosyadan stok hareketi yükle" subtitle="Çok sayıda hareketi Excel'den hazırladığın bir CSV dosyasıyla tek seferde gir.">
            <a href="{{ route('stock-movements.index') }}" class="btn-secondary">Stok hareketlerine dön</a>
        </x-page-header>
    </x-slot>

    @if (session('import_errors'))
        <div class="alert-error" role="alert">
            <div>
                <p class="font-medium">Dosya yüklenmedi, hiçbir satır kaydedilmedi. Şu hataları düzeltip tekrar dene:</p>
                <ul class="mt-2 list-disc space-y-1 ps-5">
                    @foreach (session('import_errors') as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
        <div class="card card-body h-fit">
            <h2 class="card-title">Dosyanı seç</h2>
            <form action="{{ route('stock-movements.import.store') }}" method="POST" enctype="multipart/form-data" class="mt-4">
                @csrf
                <label for="file"
                       class="flex cursor-pointer flex-col items-center rounded-xl border-2 border-dashed border-line bg-canvas/50 px-4 py-8 text-center transition hover:border-leaf-300 hover:bg-leaf-50/50 focus-within:border-leaf-400"
                       x-data="{ name: '' }">
                    <svg class="h-8 w-8 text-leaf-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M12 4v12m0-12-4 4m4-4 4 4"/></svg>
                    <span class="mt-3 text-sm font-medium text-ink" x-text="name || 'CSV dosyası seç'">CSV dosyası seç</span>
                    <span class="mt-1 text-xs text-ink-muted">.csv ya da .txt</span>
                    <input id="file" name="file" type="file" accept=".csv,.txt" required class="sr-only"
                           @change="name = $event.target.files[0]?.name || ''" />
                </label>
                <x-input-error :messages="$errors->get('file')" class="mt-2" />

                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <x-primary-button>Yükle</x-primary-button>
                    <a href="{{ route('stock-movements.import.template') }}" class="btn-secondary">Örnek dosyayı indir</a>
                </div>
            </form>
        </div>

        <div class="card card-body space-y-4 text-sm text-ink-soft">
            <h2 class="card-title">Dosya nasıl olmalı?</h2>
            <p>İlk satır başlık olmalı. Excel'de hazırlayıp <strong class="font-medium text-ink">Farklı Kaydet → CSV</strong> ile kaydedebilirsin.</p>

            <div class="overflow-x-auto rounded-lg border border-line">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>Ürün</th>
                            <th>Tür</th>
                            <th class="num">Miktar</th>
                            <th>Not</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sampleRows as [$date, $product, $type, $qty, $note])
                            <tr>
                                <td class="tabular-nums">{{ $date }}</td>
                                <td>{{ $product }}</td>
                                <td>{{ $type }}</td>
                                <td class="num">{{ $qty }}</td>
                                <td>{{ $note }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <ul class="list-disc space-y-1.5 ps-5">
                <li><strong class="font-medium text-ink">Ürün:</strong> stok kodu (SKU) ya da ürünün tam adı.</li>
                <li><strong class="font-medium text-ink">Tür:</strong> Giriş, Satış ya da Fire.</li>
                <li><strong class="font-medium text-ink">Miktar:</strong> pozitif sayı (1,5 gibi ondalıklı olabilir). <strong class="font-medium text-ink">Not</strong> sütunu boş kalabilir.</li>
                <li>Bir seferde en fazla {{ number_format(\App\Services\MovementCsvImporter::MAX_ROWS, 0, ',', '.') }} satır yüklenebilir.</li>
                <li>Dosyada tek bir hatalı satır bile varsa hiçbir satır kaydedilmez, hatalar satır numarasıyla gösterilir.</li>
            </ul>
        </div>
    </div>
</x-app-layout>
