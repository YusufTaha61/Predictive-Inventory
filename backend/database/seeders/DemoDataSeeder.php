<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Demo veri: "Demo Market" şirketi için son 12 aylık stok hareketleri.
 *
 * Gün gün bir market simüle edilir:
 *  - Satışlar hafta içi/hafta sonu, mevsim ve bayram etkisine göre değişir.
 *  - Bozulabilir ürünler son kullanma tarihi geçince "fire" olur.
 *  - Stok azalınca tedarikçiden "stok girişi" gelir.
 *  - Ürünlerde ara sıra 3-7 günlük kampanyalar (indirim) olur, o günlerde satış artar.
 *    Bazı ürünlerin yarından başlayan kampanyası da vardır (tahmin sayfasında görünsün diye).
 *
 * Aynı veriyi her seferinde üretmek için rastgelelik sabit bir sayıyla (42) başlatılır.
 * Tekrar çalıştırılabilir: önce eski Demo Market verisini siler.
 *
 * Çalıştırmak: php artisan db:seed
 * Giriş bilgileri config/demo.php dosyasında (.env: DEMO_EMAIL, DEMO_PASSWORD)
 */
class DemoDataSeeder extends Seeder
{
    private const COMPANY_NAME = 'Demo Market';
    private const DAYS = 365;

    /**
     * Ürün tanımları:
     *  price    : satış fiyatı (₺)
     *  cost     : alış fiyatı (₺), ürünü tedarikçiden kaça aldığımız
     *  unit     : ölçü birimi (Product::UNITS içinden), yoksa "adet"
     *  shelf    : bozulmayan ürünlerde ürün kartına yazılan raf ömrü (gün)
     *  daily    : ortalama günlük satış
     *  life     : teslimattan sonra kalan raf ömrü (gün), yoksa ürün bozulmaz
     *  every    : bozulabilir ürünlerde kaç günde bir sipariş verildiği
     *  over     : fazladan sipariş oranı (1.20 = %20 fazla, fireye sebep olur)
     *  season   : mevsim etkisi gücü (0 = yok)
     *  peak     : en çok satılan ay (1-12)
     *  weekend  : hafta sonu çarpanı
     *  holiday  : bayram günlerindeki çarpan
     */
    private const PRODUCTS = [
        ['name' => 'Ekmek', 'sku' => 'FRN-001', 'price' => 15, 'cost' => 10, 'daily' => 60, 'life' => 1, 'every' => 1, 'over' => 1.05, 'weekend' => 1.25],
        ['name' => 'Simit', 'sku' => 'FRN-002', 'price' => 20, 'cost' => 13, 'daily' => 25, 'life' => 1, 'every' => 1, 'over' => 1.05, 'weekend' => 1.40],
        ['name' => 'Süt 1 L', 'sku' => 'SUT-001', 'price' => 45, 'cost' => 34, 'daily' => 18, 'life' => 4, 'every' => 2, 'over' => 1.20],
        ['name' => 'Yoğurt 1 kg', 'sku' => 'SUT-002', 'price' => 110, 'cost' => 80, 'daily' => 10, 'life' => 5, 'every' => 3, 'over' => 1.10, 'season' => 0.15, 'peak' => 7],
        ['name' => 'Ayran 300 ml', 'sku' => 'SUT-003', 'price' => 20, 'cost' => 13, 'daily' => 14, 'life' => 6, 'every' => 3, 'over' => 1.15, 'season' => 0.60, 'peak' => 7],
        ['name' => 'Beyaz Peynir 500 g', 'sku' => 'SUT-004', 'price' => 220, 'cost' => 165, 'daily' => 5, 'life' => 12, 'every' => 7, 'over' => 1.10, 'holiday' => 1.8],
        ['name' => 'Yumurta 30\'lu', 'sku' => 'KHV-001', 'price' => 230, 'cost' => 185, 'unit' => 'paket', 'daily' => 6, 'life' => 8, 'every' => 5, 'over' => 1.10],
        ['name' => 'Domates', 'sku' => 'MNV-001', 'price' => 50, 'cost' => 32, 'unit' => 'kg', 'daily' => 12, 'life' => 3, 'every' => 2, 'over' => 1.20, 'season' => 0.35, 'peak' => 8],
        ['name' => 'Muz', 'sku' => 'MNV-002', 'price' => 90, 'cost' => 60, 'unit' => 'kg', 'daily' => 8, 'life' => 3, 'every' => 2, 'over' => 1.15],
        ['name' => 'Tavuk Göğüs', 'sku' => 'ET-001', 'price' => 300, 'cost' => 230, 'unit' => 'kg', 'daily' => 5, 'life' => 3, 'every' => 2, 'over' => 1.10, 'holiday' => 1.5],
        ['name' => 'Dondurma', 'sku' => 'DON-001', 'price' => 60, 'cost' => 38, 'shelf' => 180, 'daily' => 8, 'season' => 0.90, 'peak' => 7, 'weekend' => 1.35],
        ['name' => 'Su 1,5 L', 'sku' => 'ICK-001', 'price' => 20, 'cost' => 11, 'shelf' => 365, 'daily' => 30, 'season' => 0.50, 'peak' => 7],
        ['name' => 'Çay 1 kg', 'sku' => 'ICK-002', 'price' => 450, 'cost' => 330, 'unit' => 'paket', 'shelf' => 730, 'daily' => 3, 'season' => 0.20, 'peak' => 1],
        ['name' => 'Makarna 500 g', 'sku' => 'KRU-001', 'price' => 35, 'cost' => 24, 'unit' => 'paket', 'shelf' => 730, 'daily' => 10],
        ['name' => 'Ayçiçek Yağı 1 L', 'sku' => 'KRU-002', 'price' => 110, 'cost' => 85, 'shelf' => 365, 'daily' => 4],
        ['name' => 'Lokum 500 g', 'sku' => 'SKR-001', 'price' => 180, 'cost' => 110, 'unit' => 'paket', 'daily' => 2, 'life' => 60, 'every' => 14, 'over' => 1.10, 'holiday' => 6.0],
        ['name' => 'Bulaşık Deterjanı', 'sku' => 'TMZ-001', 'price' => 120, 'cost' => 80, 'shelf' => 730, 'daily' => 2],
    ];

    /** İndirim oranının satışa etkisi: %20 indirim satışı 1 + 0.20 x 2.5 = 1.5 katına çıkarır */
    private const PROMO_EFFECT = 2.5;

    /** Önümüzdeki günlerde kampanyası olan ürünler (SKU => indirim %). Yarın başlar, 4 gün sürer. */
    private const UPCOMING_PROMOS = ['ICK-001' => 20, 'DON-001' => 25, 'KRU-001' => 15, 'SUT-002' => 20];

    /**
     * Bayram günleri ve hemen öncesi (alışveriş en çok bayram arifesinde olur).
     * Aynı liste ml/src/features.py içinde de var; birini değiştirirsen diğerini de değiştir.
     */
    private const HOLIDAYS = [
        // Ramazan Bayramı 2026: 20-22 Mart
        '2026-03-17', '2026-03-18', '2026-03-19', '2026-03-20',
        // Kurban Bayramı 2026: 27-30 Mayıs
        '2026-05-24', '2026-05-25', '2026-05-26', '2026-05-27',
    ];

    public function run(): void
    {
        mt_srand(42);

        DB::transaction(function () {
            // Daha önce oluşturulmuş demo veriyi temizle (tekrar çalıştırılabilsin)
            $oldIds = Company::where('name', self::COMPANY_NAME)->pluck('id');
            User::where('email', config('demo.email'))->orWhereIn('company_id', $oldIds)->delete();
            StockMovement::whereIn('company_id', $oldIds)->delete();
            Promotion::whereIn('company_id', $oldIds)->delete();
            Product::whereIn('company_id', $oldIds)->delete();
            Company::whereIn('id', $oldIds)->delete();

            $company = Company::create(['name' => self::COMPANY_NAME]);

            User::create([
                'name' => 'Demo Kullanıcı',
                'email' => config('demo.email'),
                'password' => Hash::make(config('demo.password')),
                'company_id' => $company->id,
            ]);

            // Veri dünde biter; bugünün hareketlerini kullanıcı kendisi girebilir.
            $end = Carbon::yesterday();
            $start = $end->copy()->subDays(self::DAYS - 1);

            foreach (self::PRODUCTS as $definition) {
                $this->simulateProduct($company->id, $definition, $start, $end);
            }
        });
    }

    private function simulateProduct(int $companyId, array $p, Carbon $start, Carbon $end): void
    {
        $p += ['unit' => 'adet', 'life' => null, 'shelf' => 365, 'every' => 1, 'over' => 1.0, 'season' => 0, 'peak' => 7, 'weekend' => 1.10, 'holiday' => 1.0];
        $perishable = $p['life'] !== null;

        $productId = DB::table('products')->insertGetId([
            'company_id' => $companyId,
            'name' => $p['name'],
            'sku' => $p['sku'],
            'unit' => $p['unit'],
            'price' => $p['price'],
            'purchase_price' => $p['cost'] ?? null,
            'shelf_life_days' => $p['life'] ?? $p['shelf'],
            'created_at' => $start,
            'updated_at' => $start,
        ]);

        // Hareketler önce bir dizide toplanır, sonunda toplu olarak kaydedilir (çok daha hızlı).
        $rows = [];
        $add = function (string $type, int $qty, Carbon $day, ?string $note = null) use (&$rows, $companyId, $productId) {
            if ($qty <= 0) {
                return;
            }
            $rows[] = [
                'company_id' => $companyId,
                'product_id' => $productId,
                'type' => $type,
                'quantity' => $qty,
                'moved_on' => $day->toDateString(),
                'note' => $note,
                'created_at' => $day->copy()->setTime(9, 0),
                'updated_at' => $day->copy()->setTime(9, 0),
            ];
        };

        // Kampanyalar ve kampanya günlerindeki satış çarpanı (ekmek, simit gibi günlük ürünlerde kampanya yok)
        $promotions = $p['life'] === 1 ? [] : $this->planPromotions($start, $end);
        if (isset(self::UPCOMING_PROMOS[$p['sku']])) {
            $first = $end->copy()->addDays(2); // veri dünde bitiyor, yani kampanya yarın başlar
            $promotions[] = [$first, $first->copy()->addDays(3), self::UPCOMING_PROMOS[$p['sku']]];
        }
        $promoBoost = [];
        foreach ($promotions as [$from, $to, $discount]) {
            for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                $promoBoost[$d->toDateString()] = 1 + $discount / 100 * self::PROMO_EFFECT;
            }
            DB::table('promotions')->insert([
                'company_id' => $companyId,
                'product_id' => $productId,
                'name' => "%$discount indirim",
                'starts_on' => $from->toDateString(),
                'ends_on' => $to->toDateString(),
                'discount_percent' => $discount,
                'created_at' => $start,
                'updated_at' => $start,
            ]);
        }

        // Stok, son kullanma tarihleri farklı partiler halinde tutulur: [miktar, son gün]
        $batches = [];
        $demandHistory = [];

        // Açılış stoğu
        $opening = (int) round($p['daily'] * ($perishable ? $p['every'] : 14));
        $batches[] = [$opening, $perishable ? $start->copy()->addDays($p['life'] - 1) : null];
        $add(StockMovement::TYPE_IN, $opening, $start, 'Açılış stoğu');

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $boost = $promoBoost[$day->toDateString()] ?? 1.0;
            $expected = $this->expectedDemand($p, $day) * $boost;

            // 1) Sabah: tedarikçiden mal gelir
            if (! $day->eq($start)) {
                $order = 0;
                if ($perishable) {
                    if ((int) $start->diffInDays($day) % $p['every'] === 0) {
                        // Market sahibi geleceği bilmez: son 2 haftanın ortalamasına bakar,
                        // biraz fazla sipariş verir ve raftakini tam saymaz.
                        // Bu yüzden talep düşünce fire, artınca stoksuzluk olur.
                        $recent = array_slice($demandHistory, -14);
                        $estimate = $recent ? array_sum($recent) / count($recent) : $p['daily'];
                        // Kendi kampanyasını bildiği için kampanya günlerine fazladan sipariş verir
                        $need = $estimate * $p['every'] * $p['over'] * mt_rand(85, 115) / 100
                            * $this->averageBoost($promoBoost, $day, $p['every']);
                        $order = (int) round($need - $this->total($batches) * 0.5);
                    }
                } elseif ($this->total($batches) < $expected * 5) {
                    // Bozulmayan ürün: stok 5 günlüğün altına düşünce 3 haftalık sipariş
                    $order = (int) round($expected * 21 - $this->total($batches));
                }
                if ($order > 0) {
                    $batches[] = [$order, $perishable ? $day->copy()->addDays($p['life'] - 1) : null];
                    $add(StockMovement::TYPE_IN, $order, $day, 'Tedarikçi teslimatı');
                }
            }

            // 2) Gün içi: satışlar (önce eski parti satılır)
            $demand = $this->poisson($expected);
            $sold = min($demand, $this->total($batches));
            $add(StockMovement::TYPE_SALE, $sold, $day);
            $demandHistory[] = $demand / $boost; // kampanya etkisini ayırır, normal talebi hatırlar
            $left = $sold;
            foreach ($batches as $i => $batch) {
                $take = min($left, $batch[0]);
                $batches[$i][0] -= $take;
                $left -= $take;
                if ($left === 0) {
                    break;
                }
            }

            // 3) Akşam: tarihi geçen ürünler fire olur
            $wasted = 0;
            foreach ($batches as $i => $batch) {
                if ($batch[1] !== null && $batch[1]->lte($day)) {
                    $wasted += $batch[0];
                    $batches[$i][0] = 0;
                }
            }
            $add(StockMovement::TYPE_WASTE, $wasted, $day, 'Son kullanma tarihi geçti');

            // Ara sıra kırılma / hasar firesi
            if ($this->total($batches) > 0 && mt_rand(1, 100) <= 2) {
                foreach ($batches as $i => $batch) {
                    if ($batch[0] > 0) {
                        $batches[$i][0] -= 1;
                        break;
                    }
                }
                $add(StockMovement::TYPE_WASTE, 1, $day, 'Hasarlı ürün');
            }

            $batches = array_values(array_filter($batches, fn ($b) => $b[0] > 0));
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('stock_movements')->insert($chunk);
        }
    }

    /**
     * Geçmiş kampanyalar: 25-55 günde bir, 3-7 gün süren, %10-30 indirimli.
     *
     * @return array<int, array{0: Carbon, 1: Carbon, 2: int}> [başlangıç, bitiş, indirim %]
     */
    private function planPromotions(Carbon $start, Carbon $end): array
    {
        $promotions = [];
        $from = $start->copy()->addDays(mt_rand(14, 40));
        while (true) {
            $to = $from->copy()->addDays(mt_rand(3, 7) - 1);
            if ($to->gt($end)) {
                break;
            }
            $promotions[] = [$from, $to, [10, 15, 20, 25, 30][mt_rand(0, 4)]];
            $from = $to->copy()->addDays(mt_rand(25, 55));
        }

        return $promotions;
    }

    /** $day'den başlayan $days gün içindeki ortalama kampanya çarpanı */
    private function averageBoost(array $promoBoost, Carbon $day, int $days): float
    {
        $total = 0.0;
        for ($i = 0; $i < $days; $i++) {
            $total += $promoBoost[$day->copy()->addDays($i)->toDateString()] ?? 1.0;
        }

        return $total / $days;
    }

    /** Partilerdeki toplam stok */
    private function total(array $batches): int
    {
        return array_sum(array_column($batches, 0));
    }

    /** O gün için beklenen ortalama satış */
    private function expectedDemand(array $p, Carbon $day): float
    {
        $demand = $p['daily'];

        if ($day->isWeekend()) {
            $demand *= $p['weekend'];
        }

        // Mevsim etkisi: zirve ayında en yüksek, 6 ay sonrasında en düşük
        if ($p['season'] > 0) {
            $peakDay = Carbon::create($day->year, $p['peak'], 15)->dayOfYear;
            $angle = 2 * M_PI * ($day->dayOfYear - $peakDay) / 365;
            $demand *= 1 + $p['season'] * cos($angle);
        }

        if (in_array($day->toDateString(), self::HOLIDAYS, true)) {
            $demand *= $p['holiday'];
        }

        return max($demand, 0.1);
    }

    /** Ortalaması $mean olan rastgele tam sayı (Poisson dağılımı) */
    private function poisson(float $mean): int
    {
        if ($mean > 30) {
            // Büyük ortalamalarda normal dağılım yaklaşımı
            $u1 = max(mt_rand() / mt_getrandmax(), 1e-12);
            $u2 = mt_rand() / mt_getrandmax();
            $z = sqrt(-2 * log($u1)) * cos(2 * M_PI * $u2);

            return max(0, (int) round($mean + $z * sqrt($mean)));
        }

        $limit = exp(-$mean);
        $k = 0;
        $product = mt_rand() / mt_getrandmax();
        while ($product > $limit) {
            $k++;
            $product *= mt_rand() / mt_getrandmax();
        }

        return $k;
    }
}
