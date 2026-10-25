<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class Company extends Model
{
    /**
     * Bir şirketin birden çok kullanıcısı olabilir.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Bir şirketin birden çok ürünü olabilir.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Bir şirketin birden çok stok hareketi olabilir.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    
    /**
     * Bir şirketin birden çok kampanyası olabilir.
     */
    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }
}
