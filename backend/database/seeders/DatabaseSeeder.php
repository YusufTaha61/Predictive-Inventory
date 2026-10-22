<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Demo Market şirketi, kullanıcısı ve 1 yıllık stok hareketleri
        $this->call(DemoDataSeeder::class);
    }
}
