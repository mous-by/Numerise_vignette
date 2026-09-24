<?php

use App\Http\Controllers\Web\Admin\MairieCardTemplateController;
use Illuminate\Support\Facades\Route;

// Mairies : pas encore d'écran CRUD (W2, pas construit). Seul le réglage du modèle de carte VGT (W13) existe
// pour l'instant, gardé par la permission `mairies.update` déjà du manifeste (jamais role:, ARCHITECTURE §7).
Route::middleware(['auth', 'active', 'channel:web', 'password.changed'])->group(function () {
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
