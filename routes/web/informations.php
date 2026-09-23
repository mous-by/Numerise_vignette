<?php

use App\Http\Controllers\Web\Admin\InformationController;
use Illuminate\Support\Facades\Route;

// Informations (W6). Toutes les routes portent permission:module.action (jamais role:, ARCHITECTURE §7).
Route::middleware(['auth', 'active', 'channel:web', 'password.changed'])->group(function () {
    Route::get('/informations', [InformationController::class, 'index'])->middleware('permission:informations.view')->name('informations.index');
    Route::post('/informations', [InformationController::class, 'store'])->middleware('permission:informations.create')->name('informations.store');
    Route::put('/informations/{information}', [InformationController::class, 'update'])->middleware('permission:informations.update')->name('informations.update');
    Route::delete('/informations/{information}', [InformationController::class, 'destroy'])->middleware('permission:informations.delete')->name('informations.destroy');
});
