<?php

namespace Database\Seeders;

use App\Enums\CarStatus;
use App\Models\Car;
use App\Models\CarCategory;
use App\Models\CarImage;
use Illuminate\Database\Seeder;

/**
 * Demo Toyota catalog: 7 car lines and 15 physical cars.
 * Announced prices are illustrative reference prices for the course demo, not on-road prices.
 * Idempotent: existing categories/cars are left untouched, including admin edits.
 * Images reuse repository assets under public/assets/products (no real uploads in the repo).
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $lines = [
            ['Vios', 'vios', 'Sedan hạng B, lựa chọn quen thuộc cho gia đình trẻ.', 'assets/products/toyota_vios.png'],
            ['Avanza', 'avanza', 'MPV 7 chỗ, linh hoạt cho gia đình nhiều thế hệ.', 'assets/products/toyota_avanza.jpg'],
            ['Fortuner', 'fortuner', 'SUV khung gầm, phù hợp đường dài và địa hình.', 'assets/products/toyota_fotuner-color.png'],
            ['Raize', 'raize', 'SUV cỡ nhỏ, gọn gàng trong phố thị.', 'assets/products/toyota_RAIZE.png'],
            ['Yaris Cross', 'yaris-cross', 'SUV lai nhỏ, tiết kiệm nhiên liệu.', 'assets/products/toyota_yaris_cross.webp'],
            ['Land Cruiser', 'land-cruiser', 'SUV cao cấp, hành trình chinh phục địa hình.', 'assets/products/toyota_0002_land-cruiser-color-1.png'],
            ['Camry', 'camry', 'Sedan hạng D, tinh tế và êm ái.', 'assets/collection/camry.png'],
        ];

        $categories = [];
        $images = [];
        foreach ($lines as [$name, $slug, $description, $image]) {
            $categories[$slug] = CarCategory::firstOrCreate(
                ['slug' => $slug],
                ['brand' => 'Toyota', 'name' => $name, 'description' => $description, 'is_visible' => true],
            );
            $images[$slug] = $image;
        }

        $cars = [
            ['VIOS-2026-01', 'Toyota Vios', 'vios', 2026, 'Trắng ngọc trai', 479_000_000, CarStatus::Available],
            ['VIOS-2025-02', 'Toyota Vios', 'vios', 2025, 'Bạc kim loại', 455_000_000, CarStatus::Sold],
            ['AVANZA-2026-01', 'Toyota Avanza', 'avanza', 2026, 'Trắng ngọc trai', 598_000_000, CarStatus::Available],
            ['AVANZA-2026-02', 'Toyota Avanza', 'avanza', 2026, 'Đen dạ', 615_000_000, CarStatus::Reserved],
            ['FORTUNER-2026-01', 'Toyota Fortuner', 'fortuner', 2026, 'Trắng ngọc trai', 1_265_000_000, CarStatus::Available],
            ['FORTUNER-2025-02', 'Toyota Fortuner', 'fortuner', 2025, 'Đen dạ', 1_180_000_000, CarStatus::Available],
            ['RAIZE-2026-01', 'Toyota Raize', 'raize', 2026, 'Đỏ', 552_000_000, CarStatus::Available],
            ['RAIZE-2026-02', 'Toyota Raize', 'raize', 2026, 'Trắng', 552_000_000, CarStatus::Sold],
            ['YARIS-CROSS-2026-01', 'Toyota Yaris Cross', 'yaris-cross', 2026, 'Xanh', 765_000_000, CarStatus::Available],
            ['YARIS-CROSS-2026-02', 'Toyota Yaris Cross', 'yaris-cross', 2026, 'Bạc', 780_000_000, CarStatus::Reserved],
            ['LAND-CRUISER-2025-01', 'Toyota Land Cruiser', 'land-cruiser', 2025, 'Trắng ngọc trai', 4_286_000_000, CarStatus::Available],
            ['LAND-CRUISER-2024-02', 'Toyota Land Cruiser', 'land-cruiser', 2024, 'Đen', 4_050_000_000, CarStatus::Sold],
            ['CAMRY-2026-01', 'Toyota Camry', 'camry', 2026, 'Đen', 1_460_000_000, CarStatus::Available],
            ['CAMRY-2025-02', 'Toyota Camry', 'camry', 2025, 'Trắng ngọc trai', 1_380_000_000, CarStatus::Available],
            ['CAMRY-2025-03', 'Toyota Camry', 'camry', 2025, 'Xám', 1_380_000_000, CarStatus::Reserved],
        ];

        foreach ($cars as [$code, $name, $slug, $year, $color, $price, $status]) {
            $category = $categories[$slug];
            $car = Car::firstOrCreate(
                ['code' => $code],
                [
                    'category_id' => $category->id,
                    'slug' => strtolower(str_replace('_', '-', $code)),
                    'name' => $name,
                    'year' => $year,
                    'color' => $color,
                    'price' => $price,
                    'status' => $status,
                    'is_visible' => true,
                    'specs' => [
                        ['label' => 'Dòng xe', 'value' => $category->name],
                        ['label' => 'Năm sản xuất', 'value' => (string) $year],
                        ['label' => 'Màu ngoại thất', 'value' => $color],
                    ],
                ],
            );

            if ($car->wasRecentlyCreated && $car->images()->doesntExist()) {
                CarImage::create([
                    'car_id' => $car->id,
                    'path' => $images[$slug],
                    'sort_order' => 1,
                    'is_primary' => true,
                ]);
            }
        }
    }
}
