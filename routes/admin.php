<?php

use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Inquiry\InquiryController;
use Illuminate\Support\Facades\Route;

// Shared web/auth/active/admin middleware is registered in bootstrap/app.php.
Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
Route::get('/services/create', [ServiceController::class, 'create'])->name('services.create');
Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
Route::get('/services/{service}/edit', [ServiceController::class, 'edit'])->name('services.edit');
Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

// Shared web/auth/active/admin middleware is registered in bootstrap/app.php.
Route::get('/users', [UserController::class, 'index'])->name('users.index');
Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
Route::post('/users', [UserController::class, 'store'])->name('users.store');
Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])->name('users.status');

Route::get('/inquiries', [InquiryController::class, 'index'])->name('inquiries.index');
Route::get('/inquiries/{inquiry}', [InquiryController::class, 'show'])->name('inquiries.show');
foreach (['assign', 'start', 'note', 'close'] as $action) {
    Route::patch('/inquiries/{inquiry}/'.$action, [InquiryController::class, 'update'])->name('inquiries.'.$action);
}
