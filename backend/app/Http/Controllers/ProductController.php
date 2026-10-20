<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Giriş yapan kullanıcının şirketine ait ürünleri listeler.
     */
    public function index(Request $request): View
    {
        $products = $request->user()->company->products()
            ->orderBy('name')
            ->paginate(15);

        return view('products.index', compact('products'));
    }

    public function create(): View
    {
        return view('products.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateProduct($request);

        // products() üzerinden oluşturunca company_id otomatik doldurulur.
        $request->user()->company->products()->create($data);

        return redirect()->route('products.index')->with('status', 'Ürün eklendi.');
    }

    public function edit(Request $request, Product $product): View
    {
        $this->ensureOwnProduct($request, $product);

        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->ensureOwnProduct($request, $product);

        $product->update($this->validateProduct($request, $product));

        return redirect()->route('products.index')->with('status', 'Ürün güncellendi.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->ensureOwnProduct($request, $product);

        $product->delete();

        return redirect()->route('products.index')->with('status', 'Ürün silindi.');
    }

    /**
     * Başka şirketin ürününe erişilmeye çalışılırsa 404 döner.
     */
    private function ensureOwnProduct(Request $request, Product $product): void
    {
        abort_unless($product->company_id === $request->user()->company_id, 404);
    }

    private function validateProduct(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'nullable', 'string', 'max:100',
                Rule::unique('products')
                    ->where('company_id', $request->user()->company_id)
                    ->ignore($product),
            ],
            'unit' => ['required', 'string', 'max:20'],
            'price' => ['required', 'numeric', 'min:0'],
            'shelf_life_days' => ['nullable', 'integer', 'min:1'],
        ]);
    }
}
