<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Services\MovementCsvImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Stok hareketlerini CSV dosyasından toplu yükleme.
 * Dosyada tek bir hata bile varsa hiçbir satır kaydedilmez.
 */
class StockMovementImportController extends Controller
{
    public function create()
    {
        return view('stock-movements.import');
    }

    public function store(Request $request, MovementCsvImporter $importer)
    {
        // Dosya sunucuya ulaşamadıysa PHP'nin verdiği gerçek sebebi göster
        $upload = $request->file('file');
        if ($upload && ! $upload->isValid()) {
            return back()->withErrors(['file' => 'Dosya sunucuya yüklenemedi. Sebep: ' . $upload->getErrorMessage()]);
        }

        $request->validate([
            'file' => ['required', 'file', 'extensions:csv,txt', 'max:2048'],
        ], [
            'file.required' => 'Bir CSV dosyası seç.',
            'file.extensions' => 'Dosya .csv uzantılı olmalı. Excel\'de "Farklı Kaydet → CSV" ile kaydedebilirsin.',
            'file.max' => 'Dosya en fazla 2 MB olabilir.',
        ]);

        // 1) Dosyayı oku ve satırları kontrol et
        $products = $this->companyProducts($request)->keyBy('id');
        $result = $importer->parse(
            file_get_contents($upload->getRealPath()),
            $products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku])->values()->all(),
            Carbon::today(),
        );

        if ($result['errors']) {
            return $this->failed($result['errors']);
        }

        // 2) Yükleme sonrası hiçbir ürünün stoğu eksiye düşmemeli
        $effects = [];
        foreach ($result['rows'] as $row) {
            $effect = $row['type'] === StockMovement::TYPE_IN ? $row['quantity'] : -$row['quantity'];
            $effects[$row['product_id']] = ($effects[$row['product_id']] ?? 0) + $effect;
        }

        $format = fn ($n) => rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',');
        $stockErrors = [];
        foreach ($effects as $productId => $effect) {
            $product = $products[$productId];
            $stock = $product->currentStock();
            if ($stock + $effect < 0) {
                $stockErrors[] = "{$product->name}: mevcut stok {$format($stock)}, yükleme sonrası {$format($stock + $effect)} olur. Önce stok girişlerini ekle.";
            }
        }
        if ($stockErrors) {
            return $this->failed($stockErrors);
        }

        // 3) Hepsini birden kaydet (biri bile hata verirse hiçbiri kaydedilmez)
        DB::transaction(function () use ($result, $request) {
            $now = now();
            $records = array_map(fn ($row) => [
                'company_id' => $request->user()->company_id,
                'product_id' => $row['product_id'],
                'type' => $row['type'],
                'quantity' => $row['quantity'],
                'moved_on' => $row['moved_on'],
                'note' => $row['note'],
                'created_at' => $now,
                'updated_at' => $now,
            ], $result['rows']);

            // 500'er satırlık parçalar halinde ekle (çok satırda daha hızlı)
            foreach (array_chunk($records, 500) as $chunk) {
                DB::table('stock_movements')->insert($chunk);
            }
        });

        $count = count($result['rows']);

        return redirect()->route('stock-movements.index')
            ->with('status', "$count stok hareketi dosyadan yüklendi.");
    }

    /** Örnek dosya: şirketin kendi ürünleriyle doldurulur, Excel'de doğrudan açılır */
    public function template(Request $request)
    {
        $examples = $this->companyProducts($request)->take(3);
        $day = Carbon::yesterday()->format('d.m.Y');
        $types = ['Giriş', 'Satış', 'Fire'];

        $lines = ['Tarih;Ürün;Tür;Miktar;Not'];
        foreach ($examples->values() as $i => $product) {
            $lines[] = implode(';', [
                $day,
                $product->sku ?: $product->name,
                $types[$i],
                $i === 0 ? 20 : 3,
                $i === 0 ? 'Tedarikçi teslimatı' : '',
            ]);
        }
        if ($examples->isEmpty()) {
            $lines[] = "$day;Ekmek;Satış;40;";
        }

        // Baştaki BOM: Excel Türkçe karakterleri doğru göstersin
        return response("\xEF\xBB\xBF" . implode("\r\n", $lines) . "\r\n", 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="stok-hareketleri-ornek.csv"',
        ]);
    }

    /** Sadece giriş yapan kullanıcının şirketinin ürünleri */
    private function companyProducts(Request $request)
    {
        return Product::where('company_id', $request->user()->company_id)
            ->orderBy('name')
            ->get();
    }

    /** Hataları geri gönderir (en fazla 20 tanesini gösterir) */
    private function failed(array $errors)
    {
        $shown = array_slice($errors, 0, 20);
        if (count($errors) > 20) {
            $shown[] = '… ve ' . (count($errors) - 20) . ' hata daha.';
        }

        return back()->with('import_errors', $shown);
    }
}