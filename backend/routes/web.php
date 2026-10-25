<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductForecastController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockMovementImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('products', ProductController::class)->except('show');
    Route::get('products/{product}/forecast', ProductForecastController::class)->name('products.forecast');
    Route::get('stock-movements/import', [StockMovementImportController::class, 'create'])->name('stock-movements.import');
    Route::post('stock-movements/import', [StockMovementImportController::class, 'store'])->name('stock-movements.import.store');
    Route::get('stock-movements/import/template', [StockMovementImportController::class, 'template'])->name('stock-movements.import.template');
    Route::resource('stock-movements', StockMovementController::class)->only(['index', 'create', 'store', 'destroy']);
    Route::resource('promotions', PromotionController::class)->only(['index', 'create', 'store', 'destroy']);
});

require __DIR__.'/auth.php';
