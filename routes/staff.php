<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Inquiry\InquiryController;
use Illuminate\Support\Facades\Route;

// Shared web/auth/active/sales|admin middleware is registered in bootstrap/app.php.
Route::get('/appointments', [AppointmentController::class, 'manage'])->name('appointments.index');
Route::post('/appointments/{appointment}/assign', [AppointmentController::class, 'assign'])->name('appointments.assign');
Route::post('/appointments/{appointment}/confirm', [AppointmentController::class, 'confirm'])->name('appointments.confirm');
Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
Route::post('/appointments/{appointment}/doi', [AppointmentController::class, 'reschedule'])->name('appointments.reschedule');
Route::post('/appointments/{appointment}/conclude', [AppointmentController::class, 'conclude'])->name('appointments.conclude');

Route::get('/inquiries', [InquiryController::class, 'index'])->name('inquiries.index');
Route::get('/inquiries/{inquiry}', [InquiryController::class, 'show'])->name('inquiries.show');
foreach (['start', 'note', 'close'] as $action) {
    Route::patch('/inquiries/{inquiry}/'.$action, [InquiryController::class, 'update'])->name('inquiries.'.$action);
}
