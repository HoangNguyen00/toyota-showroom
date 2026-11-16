<?php

use App\Http\Controllers\AppointmentController;
use Illuminate\Support\Facades\Route;

// Own appointments: list + reschedule + cancel.
Route::get('/lich-hen', [AppointmentController::class, 'index'])->name('appointments.index');
Route::post('/lich-hen/{appointment}/doi', [AppointmentController::class, 'reschedule'])
    ->name('appointments.reschedule');
Route::post('/lich-hen/{appointment}/huy', [AppointmentController::class, 'cancel'])
    ->name('appointments.cancel');
