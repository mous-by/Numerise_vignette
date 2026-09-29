<?php

use App\Http\Controllers\Web\Admin\SmsController;
use Illuminate\Support\Facades\Route;

// Journal des SMS (W14). Toutes les routes portent permission:module.action (jamais role:, ARCHITECTURE §7).
Route::middleware(['auth', 'active', 'channel:web', 'password.changed'])->group(function () {
    Route::get('/sms', [SmsController::class, 'index'])->middleware('permission:sms.view')->name('sms.index');
});
