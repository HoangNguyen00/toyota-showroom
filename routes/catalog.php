<?php

// TV3 catalog public routes. Required once from routes/web.php.
// Public listing/detail only expose cars and categories that are both visible.
use App\Http\Controllers\Catalog\CarsController;
use Illuminate\Support\Facades\Route;

Route::get('/xe', [CarsController::class, 'index'])->name('cars.index');
Route::get('/xe/{car}', [CarsController::class, 'show'])->whereNumber('car')->name('cars.show');
