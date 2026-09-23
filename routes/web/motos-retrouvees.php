<?php

use App\Http\Controllers\Web\Admin\MotoRetrouveeController;
use Illuminate\Support\Facades\Route;

// Motos retrouvées (W10). Toutes les routes portent permission:module.action (jamais role:, ARCHITECTURE §7).
// Pas de route destroy : absente du cahier pour ce module.
Route::middleware(['auth', 'active', 'channel:web', 'password.changed'])->group(function () {
    Route::get('/motos-retrouvees', [MotoRetrouveeController::class, 'index'])->middleware('permission:motos-retrouvees.view')->name('motos-retrouvees.index');
    Route::post('/motos-retrouvees', [MotoRetrouveeController::class, 'store'])->middleware('permission:motos-retrouvees.create')->name('motos-retrouvees.store');
    Route::put('/motos-retrouvees/{motoRetrouvee}', [MotoRetrouveeController::class, 'update'])->middleware('permission:motos-retrouvees.update')->name('motos-retrouvees.update');
});
