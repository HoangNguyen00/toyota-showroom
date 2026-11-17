<?php

use App\Http\Controllers\Inquiry\InquiryController;
use App\Http\Controllers\Inquiry\MessageController;
use Illuminate\Support\Facades\Route;

Route::post('/tu-van', [InquiryController::class, 'store'])->middleware('throttle:public-form')->name('inquiries.store');
Route::middleware(['auth', 'ensure.active'])->group(function (): void {
    Route::get('/yeu-cau/{inquiry}/tin-nhan', [MessageController::class, 'index'])->name('inquiries.messages.index');
    Route::post('/yeu-cau/{inquiry}/tin-nhan', [MessageController::class, 'store'])->name('inquiries.messages.store');
});
