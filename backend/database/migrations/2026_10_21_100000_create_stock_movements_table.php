<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            // Hareket türü: in (giriş), sale (satış), waste (fire)
            $table->string('type', 10);
            $table->decimal('quantity', 10, 2);
            $table->date('moved_on');
            $table->string('note')->nullable();
            $table->timestamps();

            // ML modeli ürün + tarih bazında okuyacak; bu sorgu hızlı olsun.
            $table->index(['product_id', 'moved_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
