<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ForecastService;
use Illuminate\Http\Request;

class ProductForecastController extends Controller
{
    public function __invoke(Request $request, Product $product, ForecastService $service)
    {
        // Başka şirketin ürününe bakılamaz
        abort_unless((int) $product->company_id === (int) $request->user()->company_id, 404);

        // Stok eksiye düşmüşse 0 kabul et (Python eksi stok kabul etmiyor)
        $stock = max(0, $product->currentStock());

        $forecast = $service->forecast($product->id);
        $order = $service->orderSuggestion($product->id, $stock);

        // Raf ömrü girilmemişse fire riski hesaplanamaz
        $waste = $product->shelf_life_days
            ? $service->wasteRisk($product->id, $stock, $product->shelf_life_days)
            : null;

        return view('products.forecast', compact('product', 'stock', 'forecast', 'order', 'waste'));
    }
}