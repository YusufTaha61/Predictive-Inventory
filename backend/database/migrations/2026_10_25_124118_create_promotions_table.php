<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kampanya / indirim günleri. Tahmin modeli bu günlerde satışın artacağını hesaba katar.
     */
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('starts_on');
            $table->date('ends_on');                                // bu gün de dahil
            $table->decimal('discount_percent', 5, 2)->nullable(); // boşsa fiyat değişmez (sadece öne çıkarma)
            $table->timestamps();

            $table->index(['product_id', 'starts_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};