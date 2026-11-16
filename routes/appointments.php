<?php

use App\Http\Controllers\AppointmentController;
use Illuminate\Support\Facades\Route;

// Public booking (guest + customer). Throttle 3/10min/IP per endpoint like other public forms.
Route::get('/dich-vu', [AppointmentController::class, 'services'])->name('services');
Route::get('/dat-lich', [AppointmentController::class, 'create'])->name('create');
Route::post('/dat-lich', [AppointmentController::class, 'store'])
    ->middleware('throttle:public-form')
    ->name('store');
