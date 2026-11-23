<?php

// TV3 catalog public routes. Required once from routes/web.php.
// Public listing/detail only expose cars and categories that are both visible.
use App\Http\Controllers\Catalog\CarComparisonController;
use App\Http\Controllers\Catalog\CarsController;
use App\Http\Controllers\Catalog\SavedCarsController;
use Illuminate\Support\Facades\Route;

Route::get('/xe', [CarsController::class, 'index'])->name('cars.index');
// P2 đề xuất: so sánh 2 xe công khai, chỉ đọc; cùng quy tắc publiclyVisible.
Route::get('/xe/so-sanh', CarComparisonController::class)->name('cars.compare');
// P2 đề xuất: xe quan tâm lưu ID ở trình duyệt; server chỉ trả thẻ xe công khai.
Route::get('/xe-quan-tam', [SavedCarsController::class, 'index'])->name('cars.saved');
Route::get('/xe-quan-tam/the-xe', [SavedCarsController::class, 'cards'])
    ->middleware('throttle:60,1')->name('cars.saved.cards');
Route::get('/xe/{car}', [CarsController::class, 'show'])->whereNumber('car')->name('cars.show');
