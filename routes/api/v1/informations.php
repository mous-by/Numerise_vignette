<?php

use App\Http\Controllers\Api\V1\InformationController;
use Illuminate\Support\Facades\Route;

// Publique (D32, §8) : la population n'a pas de compte. Sans jeton, limitée par IP (throttle:public).
Route::middleware(['throttle:public'])->get('/informations', [InformationController::class, 'index'])->name('api.informations.index');
