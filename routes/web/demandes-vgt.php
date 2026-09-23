<?php

use App\Http\Controllers\Web\Admin\DemandeVgtController;
use App\Http\Controllers\Web\Admin\TarifVgtController;
use Illuminate\Support\Facades\Route;

// Demandes VGT (W11). Toutes les routes portent permission:module.action (jamais role:, ARCHITECTURE §7).
Route::middleware(['auth', 'active', 'channel:web', 'password.changed'])->group(function () {
    Route::get('/demandes-vgt', [DemandeVgtController::class, 'index'])->middleware('permission:demandes-vgt.view')->name('demandes-vgt.index');
    Route::post('/demandes-vgt', [DemandeVgtController::class, 'store'])->middleware('permission:demandes-vgt.create')->name('demandes-vgt.store');
    Route::put('/demandes-vgt/{demandeVgt}', [DemandeVgtController::class, 'update'])->middleware('permission:demandes-vgt.update')->name('demandes-vgt.update');
    Route::put('/demandes-vgt/{demandeVgt}/decision', [DemandeVgtController::class, 'validateRequest'])->middleware('permission:demandes-vgt.validate')->name('demandes-vgt.validate');

    Route::post('/tarifs-vgt', [TarifVgtController::class, 'store'])->middleware('permission:demandes-vgt.manage_tarifs')->name('tarifs-vgt.store');
    Route::put('/tarifs-vgt/{tarifVgt}', [TarifVgtController::class, 'update'])->middleware('permission:demandes-vgt.manage_tarifs')->name('tarifs-vgt.update');
});
