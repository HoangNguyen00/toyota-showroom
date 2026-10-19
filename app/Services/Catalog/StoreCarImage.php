<?php

namespace App\Services\Catalog;

use App\Models\Car;
use App\Models\CarImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores car images with a server-generated file name.
 * The client file name is never used on disk, only validated by CarImageRequest.
 */
class StoreCarImage
{
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

                if ($wantsPrimary) {
                    // One primary image per car; the newest upload wins.
                    CarImage::where('car_id', $lockedCar->id)->where('is_primary', true)
                        ->update(['is_primary' => false]);
                }

                $sortOrder = (int) CarImage::where('car_id', $lockedCar->id)->max('sort_order');

                return CarImage::create([
                    'car_id' => $lockedCar->id,
                    'path' => $path,
                    'sort_order' => $sortOrder + 1,
                    'is_primary' => $wantsPrimary || $sortOrder === 0,
                ]);
            });
        } catch (\Throwable $exception) {
            Storage::disk(self::DISK)->delete($path);
            throw $exception;
        }
    }

    public function delete(CarImage $image): void
    {
        // Only delete uploads owned by the app; seeded demo assets under public/assets are kept.
        if (! str_starts_with($image->path, 'assets/')) {
            Storage::disk(self::DISK)->delete($image->path);
        }

        $image->delete();
    }
}
