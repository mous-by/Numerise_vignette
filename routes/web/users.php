<?php

use App\Http\Controllers\Web\Admin\UserController;
use Illuminate\Support\Facades\Route;

// Utilisateurs (W5). Toutes les routes portent permission:module.action (jamais role:, ARCHITECTURE §7).
Route::middleware(['auth', 'active', 'channel:web', 'password.changed'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:users.update')->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete')->name('users.destroy');
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->middleware('permission:users.reset_password')->name('users.reset-password');
    Route::post('/users/{user}/revoke-access', [UserController::class, 'revokeAccess'])->middleware('permission:users.revoke_access')->name('users.revoke-access');
});
