<?php

use App\Http\Controllers\Web\VignetteVerificationController;
use Illuminate\Support\Facades\Route;

// Vérification publique d'une vignette par son QR Code : sans compte ni permission, limitée par IP
// (throttle:public), signature obligatoire dans l'adresse. Liste blanche du test d'architecture.
Route::get('/verifier/{reference}', [VignetteVerificationController::class, 'show'])->middleware('throttle:public')->name('vignette.verify');
