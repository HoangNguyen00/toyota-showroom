<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Customer\AccountController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/inquiries.php';

Route::get('/', function () {
    return app()->environment(['local', 'testing']) ? view('home') : view('welcome');
})->name('home');

// TV3 catalog: public GET /xe and /xe/{car} backed by the cars tables.
require __DIR__.'/catalog.php';

// Frontend-only preview fixtures, kept for the frontend team's static design data.
if (app()->environment(['local', 'testing'])) {
    Route::get('/xe-preview/{id}', function (int $id) {
        $data = require resource_path('data/car-details-preview.php');
        abort_unless(isset($data[$id]), 404);

        return view('cars.detail-preview', [
            'car' => $data[$id],
            'related' => array_slice(array_values(array_filter($data, fn (array $car): bool => $car['id'] !== $id)), 0, 2),
        ]);
    })->whereNumber('id')->name('cars.preview.show');

    Route::get('/collection-preview', function () {
        return view('cars.collection-preview', require resource_path('data/collection-preview.php'));
    })->name('cars.collection-preview');
}

Route::middleware('guest')->group(function (): void {
    Route::get('/dang-ky', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/dang-ky', [RegisteredUserController::class, 'store'])->name('register.store');
    Route::get('/dang-nhap', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/dang-nhap', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    // P2 đề xuất: quên mật khẩu qua Password Broker; tên route theo quy ước Laravel.
    Route::get('/quen-mat-khau', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/quen-mat-khau', [PasswordResetController::class, 'store'])
        ->middleware('throttle:10,1')->name('password.email');
    Route::get('/dat-lai-mat-khau/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/dat-lai-mat-khau', [PasswordResetController::class, 'update'])
        ->middleware('throttle:10,1')->name('password.store');
});

Route::middleware(['auth', 'ensure.active'])->group(function (): void {
    Route::get('/tai-khoan', AccountController::class)->name('account');
    Route::post('/dang-xuat', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
