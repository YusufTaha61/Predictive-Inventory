<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ForecastService
{
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.ml.url'), '/');
        $this->timeout = (int) config('services.ml.timeout');
    }

    /** Önümüzdeki 7 günün satış tahmini */
    public function forecast(int $productId): array
    {
        return $this->get("/forecast/{$productId}");
    }

    /** Kaç tane sipariş verilmeli? */
    public function orderSuggestion(int $productId, float $currentStock): array
    {
        return $this->get("/order-suggestion/{$productId}", [
            'current_stock' => $currentStock,
        ]);
    }

    /** Ne kadarı fire olur? */
    public function wasteRisk(int $productId, float $currentStock, int $shelfLifeDays): array
    {
        return $this->get("/waste-risk/{$productId}", [
            'current_stock' => $currentStock,
            'shelf_life_days' => $shelfLifeDays,
        ]);
    }

    /** Python'a istek gönderir; hata olursa ['error' => '...'] döndürür */
    private function get(string $path, array $query = []): array
    {
        try {
            $response = Http::timeout($this->timeout)->get($this->baseUrl . $path, $query);
        } catch (ConnectionException $e) {
            return ['error' => 'Tahmin servisine bağlanılamadı. Python servisi çalışıyor mu?'];
        }

        if ($response->failed()) {
            return ['error' => $response->json('detail') ?? 'Tahmin servisi hata verdi (kod ' . $response->status() . ').'];
        }

        return $response->json();
    }
}