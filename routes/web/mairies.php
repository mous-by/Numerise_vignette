<?php

use App\Http\Controllers\Web\Admin\MairieController;
use Illuminate\Support\Facades\Route;

// Mairies (W2). Toutes les routes portent permission:module.action (jamais role:, ARCHITECTURE §7).
Route::middleware(['auth', 'active', 'channel:web', 'password.changed'])->group(function () {
    Route::get('/mairies', [MairieController::class, 'index'])->middleware('permission:mairies.view')->name('mairies.index');
    Route::post('/mairies', [MairieController::class, 'store'])->middleware('permission:mairies.create')->name('mairies.store');
    Route::put('/mairies/{mairie}', [MairieController::class, 'update'])->middleware('permission:mairies.update')->name('mairies.update');
    Route::delete('/mairies/{mairie}', [MairieController::class, 'destroy'])->middleware('permission:mairies.delete')->name('mairies.destroy');
});
