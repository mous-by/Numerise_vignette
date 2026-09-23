<?php

use App\Http\Controllers\Web\Admin\ProprietaireController;
use Illuminate\Support\Facades\Route;

// Propriétaires (W7). Toutes les routes portent permission:module.action (jamais role:, ARCHITECTURE §7).
Route::middleware(['auth', 'active', 'channel:web', 'password.changed'])->group(function () {
    Route::get('/proprietaires', [ProprietaireController::class, 'index'])->middleware('permission:proprietaires.view')->name('proprietaires.index');
    Route::get('/proprietaires/search', [ProprietaireController::class, 'search'])->middleware('permission:proprietaires.view')->name('proprietaires.search');
    Route::post('/proprietaires', [ProprietaireController::class, 'store'])->middleware('permission:proprietaires.create')->name('proprietaires.store');
    Route::put('/proprietaires/{proprietaire}', [ProprietaireController::class, 'update'])->middleware('permission:proprietaires.update')->name('proprietaires.update');
    Route::delete('/proprietaires/{proprietaire}', [ProprietaireController::class, 'destroy'])->middleware('permission:proprietaires.delete')->name('proprietaires.destroy');
});
