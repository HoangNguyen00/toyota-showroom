<?php

namespace App\Services\Catalog;

use App\Models\Car;
use App\Models\CarImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Stores car images with a server-generated file name.
 * The client file name is never used on disk, only validated by CarImageRequest.
 */
class StoreCarImage
{
    public const MAX_IMAGES = 10;

    public const DISK = 'public';

    public const DIRECTORY = 'cars';

    public function handle(Car $car, UploadedFile $file, bool $wantsPrimary): CarImage
    {
        $extension = strtolower($file->extension() ?: $file->guessExtension() ?: 'jpg');
        $name = Str::uuid()->toString().'.'.$extension;

        $path = $file->storeAs(self::DIRECTORY.'/'.$car->id, $name, self::DISK);
        abort_if($path === false, 500, 'Không lưu được ảnh.');

        try {
            return DB::transaction(function () use ($car, $path, $wantsPrimary): CarImage {
                $lockedCar = Car::whereKey($car->id)->lockForUpdate()->firstOrFail();
                if ($lockedCar->images()->count() >= self::MAX_IMAGES) {
                    throw ValidationException::withMessages(['image' => 'Một xe có tối đa 10 ảnh.']);
                }
                $makePrimary = $wantsPrimary || $lockedCar->images()->where('is_primary', true)->doesntExist();

                if ($makePrimary) {
                    // One primary image per car; the newest upload wins.
                    CarImage::where('car_id', $lockedCar->id)->where('is_primary', true)
                        ->update(['is_primary' => false]);
                }

                $sortOrder = (int) CarImage::where('car_id', $lockedCar->id)->max('sort_order');

                return CarImage::create([
                    'car_id' => $lockedCar->id,
                    'path' => $path,
                    'sort_order' => $sortOrder + 1,
                    'is_primary' => $makePrimary,
                ]);
            });
        } catch (\Throwable $exception) {
            Storage::disk(self::DISK)->delete($path);
            throw $exception;
        }
    }

    public function delete(CarImage $image): void
    {
        $path = DB::transaction(function () use ($image): string {
            $car = Car::lockForUpdate()->findOrFail($image->car_id);
            $locked = $car->images()->lockForUpdate()->findOrFail($image->id);
            if ($car->is_visible && $car->images()->count() === 1) {
                throw ValidationException::withMessages(['image' => 'Hãy ẩn xe trước khi xóa ảnh cuối cùng.']);
            }
            $path = $locked->path;
            $wasPrimary = $locked->is_primary;
            $locked->delete();
            if ($wasPrimary) {
                $car->images()->orderBy('sort_order')->orderBy('id')->first()?->update(['is_primary' => true]);
            }

            return $path;
        });
        // Only delete uploads owned by the app; seeded demo assets under public/assets are kept.
        if (! str_starts_with($path, 'assets/')) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    public function update(CarImage $image, int $sortOrder, bool $makePrimary): void
    {
        DB::transaction(function () use ($image, $sortOrder, $makePrimary): void {
            $car = Car::lockForUpdate()->findOrFail($image->car_id);
            $locked = $car->images()->lockForUpdate()->findOrFail($image->id);
            if ($makePrimary) {
                $car->images()->where('is_primary', true)->update(['is_primary' => false]);
            }
            $locked->update(['sort_order' => $sortOrder, 'is_primary' => $makePrimary || $locked->is_primary]);
        });
    }
}
