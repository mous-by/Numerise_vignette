<?php

use App\Http\Controllers\Web\Admin\DeclarationController;
use Illuminate\Support\Facades\Route;

// Déclarations (W9). Toutes les routes portent permission:module.action (jamais role:, ARCHITECTURE §7).
Route::middleware(['auth', 'active', 'channel:web', 'password.changed'])->group(function () {
    Route::get('/declarations', [DeclarationController::class, 'index'])->middleware('permission:declarations.view')->name('declarations.index');
    Route::post('/declarations', [DeclarationController::class, 'store'])->middleware('permission:declarations.create')->name('declarations.store');
    Route::put('/declarations/{declaration}', [DeclarationController::class, 'update'])->middleware('permission:declarations.update')->name('declarations.update');
    Route::delete('/declarations/{declaration}', [DeclarationController::class, 'destroy'])->middleware('permission:declarations.delete')->name('declarations.destroy');
});
