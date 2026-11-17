<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Inquiry\InquiryController;
use Illuminate\Support\Facades\Route;

Route::get('/yeu-cau', [InquiryController::class, 'index'])->name('inquiries.index');
Route::get('/yeu-cau/{inquiry}', [InquiryController::class, 'show'])->name('inquiries.show');
// Own appointments: list + reschedule + cancel.
Route::get('/lich-hen', [AppointmentController::class, 'index'])->name('appointments.index');
Route::post('/lich-hen/{appointment}/doi', [AppointmentController::class, 'reschedule'])
    ->name('appointments.reschedule');
Route::post('/lich-hen/{appointment}/huy', [AppointmentController::class, 'cancel'])
    ->name('appointments.cancel');
