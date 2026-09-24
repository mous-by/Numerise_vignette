<?php

use App\Http\Controllers\Web\Admin\DemandeVgtController;
use App\Http\Controllers\Web\Admin\TarifVgtController;
use Illuminate\Support\Facades\Route;

// Demandes VGT (W11, W12). Toutes les routes portent permission:module.action (jamais role:, ARCHITECTURE §7).
Route::middleware(['auth', 'active', 'channel:web', 'password.changed'])->group(function () {
    Route::get('/demandes-vgt', [DemandeVgtController::class, 'index'])->middleware('permission:demandes-vgt.view')->name('demandes-vgt.index');
    Route::get('/demandes-vgt/{demandeId}/carte/{template}', [DemandeVgtController::class, 'card'])->whereNumber('demandeId')->middleware('permission:demandes-vgt.view')->name('demandes-vgt.card');
    Route::post('/demandes-vgt', [DemandeVgtController::class, 'store'])->middleware('permission:demandes-vgt.create')->name('demandes-vgt.store');
    Route::put('/demandes-vgt/{demandeVgt}', [DemandeVgtController::class, 'update'])->middleware('permission:demandes-vgt.update')->name('demandes-vgt.update');
    Route::put('/demandes-vgt/{demandeVgt}/decision', [DemandeVgtController::class, 'validateRequest'])->middleware('permission:demandes-vgt.validate')->name('demandes-vgt.validate');
    Route::put('/demandes-vgt/{demandeVgt}/paiement', [DemandeVgtController::class, 'confirmPaiement'])->middleware('permission:demandes-vgt.confirm_payment')->name('demandes-vgt.confirm-payment');
    Route::put('/demandes-vgt/{demandeVgt}/retrait', [DemandeVgtController::class, 'confirmRetrait'])->middleware('permission:demandes-vgt.confirm_retrait')->name('demandes-vgt.confirm-retrait');

    Route::post('/tarifs-vgt', [TarifVgtController::class, 'store'])->middleware('permission:demandes-vgt.manage_tarifs')->name('tarifs-vgt.store');
    Route::put('/tarifs-vgt/{tarifVgt}', [TarifVgtController::class, 'update'])->middleware('permission:demandes-vgt.manage_tarifs')->name('tarifs-vgt.update');
});
