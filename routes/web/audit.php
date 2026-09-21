<?php

use App\Http\Controllers\Web\Admin\AuditController;
use Illuminate\Support\Facades\Route;

// Journal d'audit (W3) : lecture seule, permission réservée au superadmin.
Route::middleware(['auth', 'active', 'channel:web', 'password.changed'])->group(function () {
    Route::get('/audit', [AuditController::class, 'index'])->middleware('permission:audit.view')->name('audit.index');
});
