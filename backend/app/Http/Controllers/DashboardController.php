<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /** Panelde gösterilen dönem (gün) */
    private const PERIOD_DAYS = 30;

    /**
     * Stoğu bu kadar günden az yetecek ürünler "azalan" sayılır.
     * Bozulabilir ürünlerde (raf ömrü 7 gün ve altı) sınır daha düşük.
     */
    private const LOW_STOCK_DAYS = 3;
    private const LOW_STOCK_DAYS_PERISHABLE = 1;

    /** Maliyet bilgisi olmadığı için maliyet = fiyatın %70'i sayılır */
    private const COST_RATE = 0.7;

    public function __invoke(Request $request)
    {
        // 1) Sadece giriş yapan kullanıcının şirketine ait ürünler
        $products = Product::where('company_id', $request->user()->company_id)
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        // Bu şirketin stok hareketleri (ürünler üzerinden süzülür)
        $movements = fn () => StockMovement::whereIn('product_id', $products->keys());

        // 2) Dönem: girilmiş en son hareketin günü ve ondan önceki 29 gün
        $lastDay = $movements()->max('moved_on');
        $end = $lastDay ? Carbon::parse($lastDay)->startOfDay() : Carbon::today();
        $start = $end->copy()->subDays(self::PERIOD_DAYS - 1);

        // 3) Ürün, gün ve türe göre satış/fire toplamları
        $rows = $movements()
            ->whereIn('type', [StockMovement::TYPE_SALE, StockMovement::TYPE_WASTE])
            ->whereDate('moved_on', '>=', $start->toDateString())
            ->whereDate('moved_on', '<=', $end->toDateString())
            ->selectRaw('product_id, type, date(moved_on) AS day, SUM(quantity) AS total')
            ->groupBy('product_id', 'type', 'day')
            ->get();

        // Grafik için her gün olsun (hareket olmayan günler 0)
        $daily = [];
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $daily[$day->toDateString()] = ['sale' => 0, 'waste' => 0];
        }

        // Her ürün için satış, fire ve son 14 günün satışı
        $perProduct = [];
        $recentFrom = $end->copy()->subDays(13)->toDateString();
        foreach ($rows as $row) {
            $qty = (float) $row->total;
            $isSale = $row->type === StockMovement::TYPE_SALE;

            $daily[$row->day][$isSale ? 'sale' : 'waste'] += $qty;

            $p = $perProduct[$row->product_id] ?? ['sale' => 0, 'waste' => 0, 'recent_sale' => 0];
            if ($isSale) {
                $p['sale'] += $qty;
                if ($row->day >= $recentFrom) {
                    $p['recent_sale'] += $qty;
                }
            } else {
                $p['waste'] += $qty;
            }
            $perProduct[$row->product_id] = $p;
        }

        // 4) Özet rakamlar
        $totals = ['sale' => 0, 'waste' => 0, 'revenue' => 0.0, 'waste_cost' => 0.0];
        foreach ($perProduct as $id => $p) {
            $price = (float) $products[$id]->price;
            $totals['sale'] += $p['sale'];
            $totals['waste'] += $p['waste'];
            $totals['revenue'] += $p['sale'] * $price;
            $totals['waste_cost'] += $p['waste'] * $price * self::COST_RATE;
        }
        $totals['waste_rate'] = $totals['sale'] + $totals['waste'] > 0
            ? 100 * $totals['waste'] / ($totals['sale'] + $totals['waste'])
            : 0;

        // 5) En çok fire veren 5 ürün (fire maliyetine göre)
        $topWaste = collect($perProduct)
            ->filter(fn ($p) => $p['waste'] > 0)
            ->map(fn ($p, $id) => [
                'product' => $products[$id],
                'waste' => $p['waste'],
                'cost' => $p['waste'] * (float) $products[$id]->price * self::COST_RATE,
                'rate' => 100 * $p['waste'] / ($p['sale'] + $p['waste']),
            ])
            ->sortByDesc('cost')
            ->take(5)
            ->values();

        // 6) Stoğu azalan ürünler: eldeki stok kaç gün yeter?
        $lowStock = $products
            ->map(function ($product) use ($perProduct) {
                $perDay = ($perProduct[$product->id]['recent_sale'] ?? 0) / 14;
                $stock = $product->currentStock();

                return [
                    'product' => $product,
                    'stock' => $stock,
                    'days_left' => $perDay > 0 ? $stock / $perDay : null,
                    'limit' => $product->shelf_life_days <= 7
                        ? self::LOW_STOCK_DAYS_PERISHABLE
                        : self::LOW_STOCK_DAYS,
                ];
            })
            ->filter(fn ($item) => $item['days_left'] !== null && $item['days_left'] < $item['limit'])
            ->sortBy('days_left')
            ->values();

        return view('dashboard', [
            'start' => $start,
            'end' => $end,
            'productCount' => $products->count(),
            'totals' => $totals,
            'daily' => $daily,
            'topWaste' => $topWaste,
            'lowStock' => $lowStock,
            'lowStockDays' => self::LOW_STOCK_DAYS,
            'lowStockDaysPerishable' => self::LOW_STOCK_DAYS_PERISHABLE,
        ]);
    }
}