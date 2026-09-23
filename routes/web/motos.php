<?php

use App\Http\Controllers\Web\Admin\MotoController;
use Illuminate\Support\Facades\Route;

// Motos (W8). Toutes les routes portent permission:module.action (jamais role:, ARCHITECTURE §7).
Route::middleware(['auth', 'active', 'channel:web', 'password.changed'])->group(function () {
    Route::get('/motos', [MotoController::class, 'index'])->middleware('permission:motos.view')->name('motos.index');
    Route::get('/motos/search', [MotoController::class, 'search'])->middleware('permission:motos.view')->name('motos.search');
    Route::get('/motos/search-stolen', [MotoController::class, 'searchStolen'])->middleware('permission:motos-retrouvees.create')->name('motos.search-stolen');
    Route::post('/motos', [MotoController::class, 'store'])->middleware('permission:motos.create')->name('motos.store');
    Route::put('/motos/{moto}', [MotoController::class, 'update'])->middleware('permission:motos.update')->name('motos.update');
    Route::delete('/motos/{moto}', [MotoController::class, 'destroy'])->middleware('permission:motos.delete')->name('motos.destroy');
});
