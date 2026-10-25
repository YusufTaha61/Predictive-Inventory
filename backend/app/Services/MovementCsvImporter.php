<?php

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * Stok hareketlerini CSV dosyasından okur ve kontrol eder.
 *
 * Beklenen sütunlar (ilk satır başlık):  tarih ; urun ; tur ; miktar ; not
 *   tarih  : 16.10.2026 ya da 2026-10-16
 *   urun   : ürünün stok kodu (SKU) ya da adı
 *   tur    : giriş / satış / fire  (in / sale / waste da olur)
 *   miktar : pozitif sayı (1,5 olabilir)
 *   not    : isteğe bağlı
 *
 * Excel'in kaydettiği dosyalar için: ; ve , ayırıcı, UTF-8 ve Türkçe (Windows-1254) kodlama desteklenir.
 * Bu sınıf veritabanına yazmaz, sadece satırları ve hataları döner.
 */
class MovementCsvImporter
{
    public const MAX_ROWS = 5000;

    private const TYPES = [
        'in' => 'in', 'giris' => 'in', 'stok girisi' => 'in',
        'sale' => 'sale', 'satis' => 'sale',
        'waste' => 'waste', 'fire' => 'waste',
    ];

    private const HEADERS = [
        'date' => ['tarih', 'date'],
        'product' => ['urun', 'urun adi', 'sku', 'stok kodu', 'product'],
        'type' => ['tur', 'hareket turu', 'type'],
        'quantity' => ['miktar', 'adet', 'quantity'],
        'note' => ['not', 'aciklama', 'note'],
    ];

    /**
     * @param  string  $content  dosyanın içeriği
     * @param  array<int, array{id:int, name:string, sku:?string}>  $products  şirketin ürünleri
     * @return array{rows: array, errors: array<string>}
     */
    public function parse(string $content, array $products, Carbon $today): array
    {
        $content = $this->toUtf8($content);
        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        if ($lines === [''] || count($lines) < 2) {
            return ['rows' => [], 'errors' => ['Dosyada başlık satırından sonra hiç satır yok.']];
        }

        $delimiter = substr_count($lines[0], ';') >= substr_count($lines[0], ',') ? ';' : ',';
        $columns = $this->findColumns(str_getcsv($lines[0], $delimiter, '"', ''));
        $missing = array_diff(['date', 'product', 'type', 'quantity'], array_keys($columns));
        if ($missing) {
            return ['rows' => [], 'errors' => [
                'Başlık satırında şu sütunlar bulunamadı: ' . implode(', ', array_map(fn ($c) => self::HEADERS[$c][0], $missing))
                . '. Örnek dosyayı indirip onun başlıklarını kullan.',
            ]];
        }

        if (count($lines) - 1 > self::MAX_ROWS) {
            return ['rows' => [], 'errors' => ['Bir seferde en fazla ' . self::MAX_ROWS . ' satır yüklenebilir. Dosyayı bölerek yükle.']];
        }

        // Ürünleri SKU'ya ve ada göre bulmak için
        $bySku = [];
        $byName = [];
        foreach ($products as $product) {
            if ($product['sku']) {
                $bySku[$this->key($product['sku'])] = $product['id'];
            }
            $byName[$this->key($product['name'])] = $product['id'];
        }

        $rows = [];
        $errors = [];
        foreach (array_slice($lines, 1) as $i => $line) {
            $lineNo = $i + 2; // Excel'deki satır numarası
            if (trim($line) === '' || trim(str_replace($delimiter, '', $line)) === '') {
                continue; // boş satır
            }
            $cells = str_getcsv($line, $delimiter, '"', '');
            $get = fn ($column) => trim($cells[$columns[$column] ?? -1] ?? '');

            $rowErrors = [];

            $date = $this->parseDate($get('date'));
            if (! $date) {
                $rowErrors[] = "tarih anlaşılamadı (\"{$get('date')}\"), 16.10.2026 şeklinde yaz";
            } elseif ($date->gt($today)) {
                $rowErrors[] = 'tarih ileri bir gün olamaz';
            }

            $productText = $get('product');
            $productId = $bySku[$this->key($productText)] ?? $byName[$this->key($productText)] ?? null;
            if (! $productId) {
                $rowErrors[] = "\"$productText\" adında ya da stok kodunda bir ürün yok";
            }

            $type = self::TYPES[$this->key($get('type'))] ?? null;
            if (! $type) {
                $rowErrors[] = "tür \"{$get('type')}\" olamaz, giriş, satış ya da fire yaz";
            }

            // Ondalıklı miktar olabilir: 1,5 ya da 1.5
            $quantity = str_replace(',', '.', $get('quantity'));
            if (! is_numeric($quantity) || (float) $quantity <= 0) {
                $rowErrors[] = "miktar pozitif bir sayı olmalı (\"{$get('quantity')}\")";
            }

            if ($rowErrors) {
                $errors[] = "Satır $lineNo: " . implode('; ', $rowErrors) . '.';

                continue;
            }

            $rows[] = [
                'line' => $lineNo,
                'moved_on' => $date,
                'product_id' => $productId,
                'type' => $type,
                'quantity' => round((float) $quantity, 2),
                'note' => mb_substr($get('note'), 0, 255) ?: null,
            ];
        }

        if (! $rows && ! $errors) {
            $errors[] = 'Dosyada yüklenecek satır bulunamadı.';
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    private function findColumns(array $header): array
    {
        $columns = [];
        foreach ($header as $index => $title) {
            $title = $this->key($title);
            foreach (self::HEADERS as $column => $names) {
                if (in_array($title, $names, true) && ! isset($columns[$column])) {
                    $columns[$column] = $index;
                }
            }
        }

        return $columns;
    }

    private function parseDate(string $value): ?Carbon
    {
        foreach (['d.m.Y', 'd/m/Y', 'Y-m-d'] as $format) {
            try {
                $date = Carbon::createFromFormat('!' . $format, $value);
            } catch (\Throwable) {
                continue;
            }
            // Geçersiz günleri (31.02.2026 gibi) elemek için geri yazıp karşılaştırılır.
            // 1.9.2026 gibi başında sıfır olmayan tarihler de kabul edilir.
            if ($date && in_array($value, [$date->format($format), $date->format(strtr($format, ['d' => 'j', 'm' => 'n']))], true)) {
                return $date;
            }
        }

        return null;
    }

    /** Karşılaştırma için: küçük harf, Türkçe karakterler sade, fazla boşluk yok */
    private function key(string $text): string
    {
        $text = strtr($text, ['İ' => 'i', 'I' => 'ı']);
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = strtr($text, ['ı' => 'i', 'ğ' => 'g', 'ü' => 'u', 'ş' => 's', 'ö' => 'o', 'ç' => 'c']);

        return preg_replace('/\s+/u', ' ', $text);
    }

    /** UTF-8 değilse Excel'in Türkçe kodlamasından (Windows-1254) çevirir, baştaki BOM'u siler */
    private function toUtf8(string $content): string
    {
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        return mb_check_encoding($content, 'UTF-8')
            ? $content
            : mb_convert_encoding($content, 'UTF-8', 'Windows-1254');
    }
}
