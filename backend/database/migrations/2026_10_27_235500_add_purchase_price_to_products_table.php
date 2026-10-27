<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ürünlere alış fiyatı ekler (isteğe bağlı).
     * Boş bırakılırsa sipariş hesabı eski varsayımı (satış fiyatının %60'ı) kullanır.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('purchase_price', 10, 2)->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('purchase_price');
        });
    }
};
