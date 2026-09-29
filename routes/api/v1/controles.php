<?php

use App\Http\Controllers\Api\V1\ControleController;
use Illuminate\Support\Facades\Route;

// Contrôle de police (W11). Pile d'une route API : auth:sanctum -> active -> channel:api -> throttle (§4.9).
Route::middleware(['auth:sanctum', 'active', 'channel:api', 'throttle:api', 'password.changed'])->group(function () {
    Route::get('/controles', [ControleController::class, 'show'])->middleware('permission:controles.check')->name('api.controles.show');
});
