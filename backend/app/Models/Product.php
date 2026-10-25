<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'sku', 'unit', 'price', 'shelf_life_days'])]
class Product extends Model
{
    /**
     * Ürünün ölçü birimi seçenekleri (miktar değil, ölçü türü).
     */
    public const UNITS = ['adet', 'kg', 'litre', 'paket', 'koli'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'shelf_life_days' => 'integer',
        ];
    }

    /**
     * Ürün bir şirkete aittir.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Bir ürünün birden çok stok hareketi olabilir.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    
    /**
     * Bir ürünün birden çok kampanyası olabilir.
     */
    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    /**
     * Mevcut stok = girişler - (satışlar + fireler)
     */
    public function currentStock(): float
    {
        $in = $this->stockMovements()
            ->where('type', StockMovement::TYPE_IN)
            ->sum('quantity');

        $out = $this->stockMovements()
            ->whereIn('type', [StockMovement::TYPE_SALE, StockMovement::TYPE_WASTE])
            ->sum('quantity');

        return (float) $in - (float) $out;
    }
}
