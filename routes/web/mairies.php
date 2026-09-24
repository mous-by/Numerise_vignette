<?php

use App\Http\Controllers\Web\Admin\MairieCardTemplateController;
use App\Http\Controllers\Web\Admin\MairieController;
use Illuminate\Support\Facades\Route;

// Mairies (W2). Toutes les routes portent permission:module.action (jamais role:, ARCHITECTURE §7).
Route::middleware(['auth', 'active', 'channel:web', 'password.changed'])->group(function () {
    Route::get('/mairies', [MairieController::class, 'index'])->middleware('permission:mairies.view')->name('mairies.index');
    Route::post('/mairies', [MairieController::class, 'store'])->middleware('permission:mairies.create')->name('mairies.store');
    Route::put('/mairies/{mairie}', [MairieController::class, 'update'])->middleware('permission:mairies.update')->name('mairies.update');
    Route::delete('/mairies/{mairie}', [MairieController::class, 'destroy'])->middleware('permission:mairies.delete')->name('mairies.destroy');

    // Carte VGT (W13) : modèle présélectionné, logo et monument de la mairie, réglés depuis l'écran Demandes VGT.
    Route::put('/mairies/{mairie}/carte-vgt-modele', [MairieCardTemplateController::class, 'update'])
        ->middleware('permission:mairies.update')->name('mairies.card-template.update');
    Route::post('/mairies/{mairie}/logo-vgt', [MairieCardTemplateController::class, 'updateLogo'])
        ->middleware('permission:mairies.update')->name('mairies.logo.update');
    Route::delete('/mairies/{mairie}/logo-vgt', [MairieCardTemplateController::class, 'resetLogo'])
        ->middleware('permission:mairies.update')->name('mairies.logo.reset');
    Route::post('/mairies/{mairie}/monument-vgt', [MairieCardTemplateController::class, 'updateMonument'])
        ->middleware('permission:mairies.update')->name('mairies.monument.update');
    Route::delete('/mairies/{mairie}/monument-vgt', [MairieCardTemplateController::class, 'resetMonument'])
        ->middleware('permission:mairies.update')->name('mairies.monument.reset');
});
