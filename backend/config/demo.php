<?php

/*
 * Demo hesap bilgileri (DemoDataSeeder bu hesabı oluşturur).
 *
 * Şifre bilerek "password" gibi yaygın bir şifre değil: tarayıcılar yaygın şifreleri
 * "sızdırılmış şifre" listesinde bulup uyarı veriyor. İstersen .env dosyasında değiştirebilirsin.
 *
 * Demo hesap korumalıdır: silinemez, şifresi ve e-postası değiştirilemez (User::isDemo()).
 * Giriş sayfasındaki demo kutusu sadece APP_ENV=local iken görünür.
 */
return [
    'email' => env('DEMO_EMAIL', 'demo@predictive-inventory.test'),
    'password' => env('DEMO_PASSWORD', 'PiDemo-Stok-2026!'),
];
