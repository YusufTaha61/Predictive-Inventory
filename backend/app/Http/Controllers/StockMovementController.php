<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    /**
     * Şirketin stok hareketlerini en yeniden eskiye listeler.
     */
    public function index(Request $request): View
    {
        $movements = $request->user()->company->stockMovements()
            ->with('product')
            ->latest('moved_on')
            ->latest('id')
            ->paginate(20);

        return view('stock-movements.index', compact('movements'));
    }

    /**
     * Yeni hareket formu. Ürün listesi sadece bu şirketin ürünleridir.
     */
    public function create(Request $request): View
    {
        $products = $request->user()->company->products()->orderBy('name')->get();

        return view('stock-movements.create', compact('products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => [
                'required',
                // Ürün mutlaka kullanıcının kendi şirketine ait olmalı.
                Rule::exists('products', 'id')->where('company_id', $request->user()->company_id),
            ],
            'type' => ['required', Rule::in(array_keys(StockMovement::TYPES))],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'moved_on' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        // Satış veya fire, eldeki stoktan fazla olamaz.
        if ($data['type'] !== StockMovement::TYPE_IN) {
            $product = Product::find($data['product_id']);
            $stock = $product->currentStock();

            if ($data['quantity'] > $stock) {
                $stockText = rtrim(rtrim(number_format($stock, 2, ',', '.'), '0'), ',');

                throw ValidationException::withMessages([
                    'quantity' => "Stokta yeterli ürün yok. Mevcut stok: {$stockText} ".ucfirst($product->unit),
                ]);
            }
        }

        // stockMovements() üzerinden oluşturunca company_id otomatik doldurulur.
        $request->user()->company->stockMovements()->create($data);

        return redirect()->route('stock-movements.index')->with('status', 'Hareket kaydedildi.');
    }

    public function destroy(Request $request, StockMovement $stockMovement): RedirectResponse
    {
        // Başka şirketin kaydına erişilmeye çalışılırsa 404 döner.
        abort_unless($stockMovement->company_id === $request->user()->company_id, 404);

        $stockMovement->delete();

        return redirect()->route('stock-movements.index')->with('status', 'Hareket silindi.');
    }
}
