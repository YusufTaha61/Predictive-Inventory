<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['product_id', 'name', 'starts_on', 'ends_on', 'discount_percent'])]
class Promotion extends Model
{
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'discount_percent' => 'decimal:2',
        ];
    }

    /**
     * Kampanya bir şirkete aittir.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Kampanya bir ürüne aittir.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Kampanya boyunca ürünün satış fiyatı.
     */
    public function priceFor(Product $product): float
    {
        $price = (float) $product->price;

        return $this->discount_percent
            ? round($price * (1 - (float) $this->discount_percent / 100), 2)
            : $price;
    }

    /**
     * Kampanya kaç gün sürüyor (başlangıç ve bitiş günü dahil).
     */
    public function days(): int
    {
        return (int) $this->starts_on->diffInDays($this->ends_on) + 1;
    }

    /**
     * "active" (devam ediyor), "upcoming" (ileride) ya da "past" (bitti).
     */
    public function status(?Carbon $today = null): string
    {
        $today = ($today ?? Carbon::today())->copy()->startOfDay();

        if ($this->ends_on->lt($today)) {
            return 'past';
        }

        return $this->starts_on->gt($today) ? 'upcoming' : 'active';
    }
}