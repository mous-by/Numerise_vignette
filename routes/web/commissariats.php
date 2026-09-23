<?php

use App\Http\Controllers\Web\Admin\CommissariatController;
use Illuminate\Support\Facades\Route;

// Commissariats (W1). Toutes les routes portent permission:module.action (jamais role:, ARCHITECTURE §7).
Route::middleware(['auth', 'active', 'channel:web', 'password.changed'])->group(function () {
    Route::get('/commissariats', [CommissariatController::class, 'index'])->middleware('permission:commissariats.view')->name('commissariats.index');
    Route::post('/commissariats', [CommissariatController::class, 'store'])->middleware('permission:commissariats.create')->name('commissariats.store');
    Route::put('/commissariats/{commissariat}', [CommissariatController::class, 'update'])->middleware('permission:commissariats.update')->name('commissariats.update');
    Route::delete('/commissariats/{commissariat}', [CommissariatController::class, 'destroy'])->middleware('permission:commissariats.delete')->name('commissariats.destroy');
});
