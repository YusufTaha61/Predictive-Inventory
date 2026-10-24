<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Stok Hareketleri</h2>
            <a href="{{ route('stock-movements.create') }}">
                <x-primary-button type="button">Yeni Hareket</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">{{ session('status') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($movements->isEmpty())
                        <p>Henüz hareket yok. "Yeni Hareket" ile ilk giriş, satış veya fire kaydını ekle.</p>
                    @else
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b">
                                    <th class="py-2">Tarih</th>
                                    <th class="py-2">Ürün</th>
                                    <th class="py-2">Tür</th>
                                    <th class="py-2">Miktar</th>
                                    <th class="py-2">Not</th>
                                    <th class="py-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($movements as $movement)
                                    @php
                                        $color = match ($movement->type) {
                                            'in' => 'bg-green-100 text-green-800',
                                            'sale' => 'bg-blue-100 text-blue-800',
                                            'waste' => 'bg-red-100 text-red-800',
                                            default => 'bg-gray-100 text-gray-800',
                                        };
                                    @endphp
                                    <tr class="border-b">
                                        <td class="py-2">{{ $movement->moved_on->format('d.m.Y') }}</td>
                                        <td class="py-2">{{ $movement->product->name }}</td>
                                        <td class="py-2">
                                            <span class="px-2 py-1 rounded text-xs {{ $color }}">{{ $movement->typeLabel() }}</span>
                                        </td>
                                        <td class="py-2">{{ rtrim(rtrim(number_format($movement->quantity, 2, ',', '.'), '0'), ',') }} {{ ucfirst($movement->product->unit) }}</td>
                                        <td class="py-2">{{ $movement->note ?? '-' }}</td>
                                        <td class="py-2">
                                            <div class="flex justify-end items-center gap-2">
                                                <form method="POST" action="{{ route('stock-movements.destroy', $movement) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="px-3 py-1 rounded-md border border-red-200 bg-red-50 text-red-700 text-xs font-medium hover:bg-red-100">Sil</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="mt-4">{{ $movements->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
