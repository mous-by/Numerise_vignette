<?php

use App\Http\Controllers\Api\V1\PublicDemandeVgtController;
use App\Http\Controllers\Api\V1\PublicMotoRetrouveeController;
use Illuminate\Support\Facades\Route;

// Routes publiques de la population (D32, §8) : sans compte ni jeton. Lecture limitée par IP (throttle:public),
// écriture et suivi limités par IP et par matricule (throttle:public-write). Aucune donnée personnelle renvoyée.
Route::middleware('throttle:public')->group(function () {
    Route::get('/motos-retrouvees', [PublicMotoRetrouveeController::class, 'index'])->name('api.motos-retrouvees.index');
    Route::get('/mairies', [PublicMotoRetrouveeController::class, 'mairies'])->name('api.mairies.index');
});

Route::middleware('throttle:public-write')->group(function () {
    Route::post('/demandes-vgt', [PublicDemandeVgtController::class, 'store'])->name('api.demandes-vgt.store');
    Route::post('/demandes-vgt/suivi', [PublicDemandeVgtController::class, 'suivi'])->name('api.demandes-vgt.suivi');
});
