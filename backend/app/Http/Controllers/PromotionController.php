<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PromotionController extends Controller
{
    /** Bir kampanya en fazla bu kadar gün sürebilir */
    private const MAX_DAYS = 60;

    /**
     * Önce devam eden ve yaklaşan kampanyalar, sonra bitmiş olanlar.
     */
    public function index(Request $request): View
    {
        $promotions = $request->user()->company->promotions()
            ->with('product')
            ->orderByRaw('CASE WHEN ends_on >= ? THEN 0 ELSE 1 END', [now()->toDateString()])
            ->orderBy('starts_on')
            ->paginate(15);

        return view('promotions.index', compact('promotions'));
    }

    /**
     * Yeni kampanya formu. Ürün listesi sadece bu şirketin ürünleridir.
     */
    public function create(Request $request): View
    {
        $products = $request->user()->company->products()->orderBy('name')->get();
        $selected = $request->integer('product_id') ?: null;

        return view('promotions.create', compact('products', 'selected'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => [
                'required',
                // Ürün mutlaka kullanıcının kendi şirketine ait olmalı.
                Rule::exists('products', 'id')->where('company_id', $request->user()->company_id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'discount_percent' => ['nullable', 'numeric', 'min:1', 'max:90'],
        ], [
            'ends_on.after_or_equal' => 'Bitiş tarihi başlangıç tarihinden önce olamaz.',
        ]);

        // Kampanya çok uzun olamaz
        $promotion = new Promotion($data);
        if ($promotion->days() > self::MAX_DAYS) {
            throw ValidationException::withMessages([
                'ends_on' => 'Bir kampanya en fazla '.self::MAX_DAYS.' gün sürebilir.',
            ]);
        }

        // Aynı ürünün tarihleri çakışan iki kampanyası olamaz
        $product = Product::find($data['product_id']);
        $overlaps = $product->promotions()
            ->whereDate('starts_on', '<=', $data['ends_on'])
            ->whereDate('ends_on', '>=', $data['starts_on'])
            ->exists();
        if ($overlaps) {
            throw ValidationException::withMessages([
                'starts_on' => 'Bu ürünün bu tarihlerde başka bir kampanyası var.',
            ]);
        }

        // promotions() üzerinden oluşturunca company_id otomatik doldurulur.
        $request->user()->company->promotions()->create($data);

        return redirect()->route('promotions.index')
            ->with('status', 'Kampanya eklendi. Tahminler bu günleri hesaba katacak.');
    }

    public function destroy(Request $request, Promotion $promotion): RedirectResponse
    {
        // Başka şirketin kaydına erişilmeye çalışılırsa 404 döner.
        abort_unless($promotion->company_id === $request->user()->company_id, 404);

        $promotion->delete();

        return redirect()->route('promotions.index')->with('status', 'Kampanya silindi.');
    }
}
