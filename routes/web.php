<?php

use App\Http\Controllers\AbonoController;
use App\Http\Controllers\ApartadoController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::resource('products', ProductController::class)->except('show');

    Route::resource('apartados', ApartadoController::class)->only(['index', 'create', 'store', 'show']);
    Route::patch('apartados/{apartado}/cancelar', [ApartadoController::class, 'cancel'])->name('apartados.cancel');
    Route::post('apartados/{apartado}/abonos', [AbonoController::class, 'store'])->name('apartados.abonos.store');
});

require __DIR__.'/settings.php';
