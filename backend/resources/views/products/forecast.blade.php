@php
    $methodLabels = [
        'ml' => 'Makine öğrenmesi modeli',
        'seasonal_average' => 'Son 4 haftanın aynı günlerinin ortalaması',
        'history_average' => 'Mevcut satış günlerinin ortalaması',
        'no_data' => 'Veri yok',
    ];
    $riskLabels = [
        'none' => ['Yok', 'text-leaf-700'],
        'low' => ['Düşük', 'text-leaf-700'],
        'medium' => ['Orta', 'text-mustard-700'],
        'high' => ['Yüksek', 'text-tomato-700'],
    ];
    $dayNames = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
    $shortDayNames = ['Paz', 'Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt'];

    // 1,50 → 1,5 ; 2,00 → 2
    $qty = fn ($value) => rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');

    $days = $forecast['forecast'] ?? [];
    $maxUnits = collect($days)->max('units') ?: 1;
    $hasPromo = collect($days)->contains('on_promo', true);
@endphp

<x-app-layout>
    <x-slot name="title">Tahmin: {{ $product->name }}</x-slot>
    <x-slot name="header">
        <x-page-header :title="$product->name" subtitle="Satış tahmini, sipariş önerisi ve fire riski.">
            <a href="{{ route('promotions.create', ['product_id' => $product->id]) }}" class="btn-secondary">Kampanya ekle</a>
            <a href="{{ route('products.index') }}" class="btn-secondary">Ürünlere dön</a>
        </x-page-header>
    </x-slot>

    @if (isset($forecast['error']))
        <div class="alert-warning" role="alert">
            <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.3 3.1a2 2 0 0 1 3.4 0l6 10.4A2 2 0 0 1 16 16.5H4a2 2 0 0 1-1.7-3l6-10.4ZM10 7a.9.9 0 0 0-.9.9v3a.9.9 0 1 0 1.8 0v-3A.9.9 0 0 0 10 7Zm0 7.2a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>
            <span>{{ $forecast['error'] }}</span>
        </div>
    @else
        @foreach ($forecast['warnings'] ?? [] as $warning)
            <div class="alert-warning" role="status">
                <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16Zm-.9 4.5a.9.9 0 1 1 1.8 0v4a.9.9 0 1 1-1.8 0v-4Zm.9 7.9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>
                <span>{{ ucfirst($warning) }}</span>
            </div>
        @endforeach

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Öne çıkan karar: sipariş önerisi --}}
            <div class="card overflow-hidden lg:col-span-1">
                <div class="bg-leaf-500 p-6 text-white">
                    <p class="text-sm text-white/80">Sipariş önerisi</p>
                    @if (isset($order['suggested_order']))
                        <p class="mt-2 font-display text-5xl font-semibold tracking-tight tabular-nums">{{ number_format($order['suggested_order'], 0, ',', '.') }}</p>
                        <p class="mt-1 text-white/85">{{ $product->unit }} sipariş ver</p>
                    @else
                        <p class="mt-2 font-display text-2xl font-semibold">Hesaplanamadı</p>
                        <p class="mt-1 text-white/85">{{ $order['error'] ?? 'Yeterli veri yok' }}</p>
                    @endif
                </div>
                <dl class="divide-y divide-line text-sm">
                    <div class="flex justify-between px-6 py-3">
                        <dt class="text-ink-muted">Elindeki stok</dt>
                        <dd class="font-medium tabular-nums">{{ $qty($stock) }} {{ $product->unit }}</dd>
                    </div>
                    <div class="flex justify-between px-6 py-3">
                        <dt class="text-ink-muted">Hesap</dt>
                        <dd class="font-medium">7 günlük satış + %20 yedek − stok</dd>
                    </div>
                </dl>
            </div>

            <div class="card card-body">
                <p class="stat-label">Önümüzdeki 7 gün beklenen satış</p>
                @if ($forecast['total_next_7_days'] !== null)
                    <p class="stat-value">{{ $qty($forecast['total_next_7_days']) }} <span class="text-lg font-medium text-ink-muted">{{ $product->unit }}</span></p>
                    <p class="stat-note">Günde ortalama {{ number_format($forecast['total_next_7_days'] / 7, 1, ',', '.') }}</p>
                @else
                    <p class="stat-value text-ink-muted">—</p>
                    <p class="stat-note">Bu ürünün henüz satışı yok.</p>
                @endif
            </div>

            <div class="card card-body">
                <p class="stat-label">Fire riski (eldeki stok)</p>
                @if ($waste === null)
                    <p class="stat-value text-ink-muted">—</p>
                    <p class="stat-note">Raf ömrü girilmemiş, fire riski hesaplanamıyor.</p>
                @elseif (isset($waste['risk_level']))
                    @php($risk = $riskLabels[$waste['risk_level']] ?? [$waste['risk_level'], 'text-ink'])
                    <p class="stat-value {{ $risk[1] }}">{{ $risk[0] }}</p>
                    <p class="stat-note">
                        Tarihi geçmeden satılamayacak tahmini miktar:
                        <span class="font-medium text-ink">{{ $qty($waste['units_at_risk']) }} {{ $product->unit }}</span>
                    </p>
                @else
                    <p class="stat-value text-ink-muted">—</p>
                    <p class="stat-note">{{ $waste['error'] ?? 'Hesaplanamadı' }}</p>
                @endif
            </div>
        </div>

        {{-- Günlük satış tahmini --}}
        <div class="card card-body">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="card-title">Günlük satış tahmini</h2>
                <div class="flex gap-4 text-sm text-ink-soft">
                    <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-leaf-500"></span>Normal gün</span>
                    <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-mustard-500"></span>Kampanya günü</span>
                </div>
            </div>

            @if (empty($days))
                <x-empty-state title="Tahmin yok" text="Bu ürünün stok hareketi girilince tahmin burada görünür." />
            @else
                <div class="mt-6 grid grid-cols-7 gap-2 sm:gap-4">
                    @foreach ($days as $day)
                        @php
                            $date = \Illuminate\Support\Carbon::parse($day['date']);
                            $isPromo = $day['on_promo'] ?? false;
                        @endphp
                        <div class="flex flex-col items-center" title="{{ $date->format('d.m.Y') }} {{ $dayNames[$date->dayOfWeek] }}: {{ number_format($day['units'], 1, ',', '.') }} {{ $product->unit }}">
                            <span class="text-sm font-medium tabular-nums">{{ number_format($day['units'], 0, ',', '.') }}</span>
                            <div class="mt-1.5 flex h-44 w-full items-end">
                                <div class="w-full rounded-t-md {{ $isPromo ? 'bg-mustard-500' : 'bg-leaf-500' }}"
                                     style="height: {{ max(2, round(100 * $day['units'] / $maxUnits)) }}%"></div>
                            </div>
                            <div class="w-full border-t border-line pt-2 text-center">
                                <p class="text-xs font-medium sm:text-sm">
                                    <span class="sm:hidden">{{ $shortDayNames[$date->dayOfWeek] }}</span>
                                    <span class="hidden sm:inline">{{ $dayNames[$date->dayOfWeek] }}</span>
                                </p>
                                <p class="text-xs tabular-nums text-ink-muted">{{ $date->format('d.m') }}</p>
                                @if ($isPromo)
                                    <p class="mt-1 hidden text-xs font-medium text-mustard-700 sm:block">Kampanya</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="mt-6 space-y-1 border-t border-line pt-4 text-sm text-ink-muted">
                <p>
                    Yöntem: <span class="font-medium text-ink">{{ $methodLabels[$forecast['method']] ?? $forecast['method'] }}</span>.
                    @if (! empty($days))
                        {{ \Illuminate\Support\Carbon::parse($days[0]['date'])->subDay()->format('d.m.Y') }} tarihine kadarki satışlar kullanıldı.
                    @endif
                </p>
                @if ($hasPromo)
                    <p>Sarı günlerde kampanya var; tahmin bu günlerde satışın artacağını hesaba kattı.</p>
                @endif
                @if ($forecast['method'] !== 'ml' && ! empty($days))
                    <p>
                        Bu ürün için makine öğrenmesi modeli kullanılamadı (ürün yeni ya da model bu ürünü henüz tanımıyor).
                        Modeli güncel veriyle yeniden eğitince (php artisan ml:train) bu ürün de modele dahil olur.
                    </p>
                @endif
            </div>
        </div>
    @endif
</x-app-layout>
