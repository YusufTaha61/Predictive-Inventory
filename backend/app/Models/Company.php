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
}
