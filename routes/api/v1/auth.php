<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Support\Facades\Route;

// Public : disponibilité et connexion (limitation par IP en plus des 5 tentatives par numéro).
Route::get('/health', HealthController::class)->name('api.health');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:20,1')->name('api.login');

// Pile d'une route API : auth:sanctum -> active -> channel:api -> throttle (ARCHITECTURE §14).
Route::middleware(['auth:sanctum', 'active', 'channel:api', 'throttle:api'])->group(function () {
    // Accessibles même avec le jeton restreint (mot de passe temporaire).
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::put('/auth/password', [AuthController::class, 'password'])->name('api.password');

    Route::middleware('password.changed')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me'])->name('api.me');
    });
});
