<?php

use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Catalog\AdminCarCategoryController;
use App\Http\Controllers\Catalog\AdminCarController;
use App\Http\Controllers\Catalog\AdminCarImageController;
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

// TV3 catalog — admin.car-categories.* and admin.cars.*
Route::get('/car-categories', [AdminCarCategoryController::class, 'index'])->name('car-categories.index');
Route::get('/car-categories/create', [AdminCarCategoryController::class, 'create'])->name('car-categories.create');
Route::post('/car-categories', [AdminCarCategoryController::class, 'store'])->name('car-categories.store');
Route::get('/car-categories/{category}/edit', [AdminCarCategoryController::class, 'edit'])->name('car-categories.edit');
Route::put('/car-categories/{category}', [AdminCarCategoryController::class, 'update'])->name('car-categories.update');
Route::delete('/car-categories/{category}', [AdminCarCategoryController::class, 'destroy'])->name('car-categories.destroy');

Route::get('/cars', [AdminCarController::class, 'index'])->name('cars.index');
Route::get('/cars/create', [AdminCarController::class, 'create'])->name('cars.create');
Route::post('/cars', [AdminCarController::class, 'store'])->name('cars.store');
Route::get('/cars/{car}/edit', [AdminCarController::class, 'edit'])->name('cars.edit');
Route::put('/cars/{car}', [AdminCarController::class, 'update'])->name('cars.update');
Route::delete('/cars/{car}', [AdminCarController::class, 'destroy'])->name('cars.destroy');
Route::post('/cars/{car}/images', [AdminCarImageController::class, 'store'])->name('cars.images.store');
Route::delete('/cars/{car}/images/{image}', [AdminCarImageController::class, 'destroy'])->name('cars.images.destroy');
