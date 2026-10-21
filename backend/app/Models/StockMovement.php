<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'type', 'quantity', 'moved_on', 'note'])]
class StockMovement extends Model
{
    public const TYPE_IN = 'in';
    public const TYPE_SALE = 'sale';
    public const TYPE_WASTE = 'waste';

    /**
     * Hareket türleri ve ekranda görünen Türkçe adları.
     */
    public const TYPES = [
        self::TYPE_IN => 'Giriş',
        self::TYPE_SALE => 'Satış',
        self::TYPE_WASTE => 'Fire',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'moved_on' => 'date',
        ];
    }

    /**
     * Hareket bir şirkete aittir.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Hareket bir ürüne aittir.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Türün Türkçe adı (ör. "Satış").
     */
    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
