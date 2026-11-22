<?php

// TV3 catalog public routes. Required once from routes/web.php.
// Public listing/detail only expose cars and categories that are both visible.
use App\Http\Controllers\Catalog\CarComparisonController;
use App\Http\Controllers\Catalog\CarsController;
use Illuminate\Support\Facades\Route;

Route::get('/xe', [CarsController::class, 'index'])->name('cars.index');
// P2 đề xuất: so sánh 2 xe công khai, chỉ đọc; cùng quy tắc publiclyVisible.
Route::get('/xe/so-sanh', CarComparisonController::class)->name('cars.compare');
Route::get('/xe/{car}', [CarsController::class, 'show'])->whereNumber('car')->name('cars.show');
