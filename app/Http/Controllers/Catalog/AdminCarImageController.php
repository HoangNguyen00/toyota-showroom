<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\CarImageRequest;
use App\Models\Car;
use App\Models\CarImage;
use App\Services\Catalog\StoreCarImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AdminCarImageController extends Controller
{
    public function store(CarImageRequest $request, Car $car, StoreCarImage $service): RedirectResponse
    {
        Gate::authorize('uploadImage', $car);
        $service->handle($car, $request->file('image'), $request->boolean('is_primary'));

        return redirect()->route('admin.cars.edit', $car)->with('status', 'Đã tải ảnh lên.');
    }

    public function destroy(Car $car, CarImage $image, StoreCarImage $service): RedirectResponse
    {
        Gate::authorize('deleteImage', $car);
        abort_unless($image->car_id === $car->id, 404);
        $service->delete($image);

        return redirect()->route('admin.cars.edit', $car)->with('status', 'Đã xóa ảnh.');
    }
}
