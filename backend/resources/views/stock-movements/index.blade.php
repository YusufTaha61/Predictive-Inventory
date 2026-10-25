@php
    $typeBadges = ['in' => 'badge-ink', 'sale' => 'badge-green', 'waste' => 'badge-red'];
@endphp

<x-app-layout>
    <x-slot name="title">Stok Hareketleri</x-slot>
    <x-slot name="header">
        <x-page-header title="Stok hareketleri" subtitle="Gelen mal, satış ve fire kayıtları. Ürünlerin stoğu bu kayıtlardan hesaplanır.">
            <a href="{{ route('stock-movements.import') }}" class="btn-secondary">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M12 4v12m0-12-4 4m4-4 4 4"/></svg>
                Dosyadan yükle
            </a>
            <a href="{{ route('stock-movements.create') }}" class="btn-primary">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 4a1 1 0 0 1 1 1v4h4a1 1 0 1 1 0 2h-4v4a1 1 0 1 1-2 0v-4H5a1 1 0 1 1 0-2h4V5a1 1 0 0 1 1-1Z"/></svg>
                Yeni hareket
            </a>
        </x-page-header>
    </x-slot>

    <div class="card">
        @if ($movements->isEmpty())
            <x-empty-state title="Henüz stok hareketi yok" text="Gelen malı, satışı ve fireyi tek tek gir ya da Excel'den CSV dosyasıyla toplu yükle.">
                <a href="{{ route('stock-movements.create') }}" class="btn-primary">Yeni hareket</a>
                <a href="{{ route('stock-movements.import') }}" class="btn-secondary">Dosyadan yükle</a>
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>Ürün</th>
                            <th>Tür</th>
                            <th class="num">Stoğa etkisi</th>
                            <th>Not</th>
                            <th class="num"><span class="sr-only">İşlem</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($movements as $movement)
                            @php
                                // Giriş stoğu artırır, satış ve fire azaltır
                                $effect = $movement->type === \App\Models\StockMovement::TYPE_IN ? $movement->quantity : -$movement->quantity;
                            @endphp
                            <tr>
                                <td class="whitespace-nowrap tabular-nums text-ink-soft">{{ $movement->moved_on->format('d.m.Y') }}</td>
                                <td class="font-medium">{{ $movement->product->name }}</td>
                                <td><span class="{{ $typeBadges[$movement->type] ?? 'badge-gray' }}">{{ $movement->typeLabel() }}</span></td>
                                <td class="num font-medium {{ $effect > 0 ? 'text-leaf-600' : 'text-ink' }}">{{ $effect > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format($effect, 2, ',', '.'), '0'), ',') }} {{ $movement->product->unit }}</td>
                                <td class="max-w-xs truncate text-ink-muted">{{ $movement->note ?? '' }}</td>
                                <td class="num">
                                    <form action="{{ route('stock-movements.destroy', $movement) }}" method="POST"
                                          onsubmit="return confirm('Bu hareketi silmek istediğine emin misin?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="action-link-danger">Sil</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($movements->hasPages())
                <div class="border-t border-line px-4 py-3">{{ $movements->links() }}</div>
            @endif
        @endif
    </div>
</x-app-layout>
