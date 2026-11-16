<?php

namespace Database\Seeders;

use App\Enums\CarStatus;
use App\Enums\ServiceType;
use App\Models\Car;
use App\Models\CarCategory;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Dữ liệu demo TV5: dịch vụ bảo dưỡng/bảo hành và xe Toyota hiển thị công khai.
 * Dùng firstOrCreate để chạy lại không bị trùng (idempotent).
 */
class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        // Dịch vụ bookable cho /dat-lich và /dich-vu.
        Service::firstOrCreate(
            ['slug' => 'bao-duong-10k'],
            [
                'name' => 'Bảo dưỡng định kỳ 10.000km',
                'type' => ServiceType::Maintenance,
                'description' => 'Kiểm tra và bảo dưỡng định kỳ theo mốc km: thay dầu, lọc gió, kiểm động cơ và phanh.',
                'allow_booking' => true,
                'is_visible' => true,
            ]
        );
        Service::firstOrCreate(
            ['slug' => 'bao-hanh-hang'],
            [
                'name' => 'Bảo hành hãng',
                'type' => ServiceType::Warranty,
                'description' => 'Bảo hành chính hãng Toyota theo chính sách nhà sản xuất.',
                'allow_booking' => true,
                'is_visible' => true,
            ]
        );

        // Nhóm xe + xe hiển thị cho lịch xem xe (đầy đủ dòng Toyota demo).
        $categories = collect([
            'vios' => ['name' => 'Vios', 'cars' => [
                ['TOY-DEMO-001', 'toyota-vios-1-5g', 'Toyota Vios 1.5G', 'Trắng', 539000000],
                ['TOY-DEMO-002', 'toyota-vios-1-5e', 'Toyota Vios 1.5E', 'Đen', 574000000],
            ]],
            'yaris' => ['name' => 'Yaris', 'cars' => [
                ['TOY-DEMO-003', 'toyota-yaris-1-5g', 'Toyota Yaris 1.5G', 'Bạc', 557000000],
            ]],
            'corolla-cross' => ['name' => 'Corolla Cross', 'cars' => [
                ['TOY-DEMO-004', 'toyota-corolla-cross-1-8v', 'Toyota Corolla Cross 1.8V', 'Đỏ', 815000000],
                ['TOY-DEMO-005', 'toyota-corolla-cross-1-8hev', 'Toyota Corolla Cross HEV', 'Xám', 899000000],
            ]],
            'camry' => ['name' => 'Camry', 'cars' => [
                ['TOY-DEMO-006', 'toyota-camry-2-0g', 'Toyota Camry 2.0G', 'Xanh đen', 1119000000],
                ['TOY-DEMO-007', 'toyota-camry-2-5hev', 'Toyota Camry 2.5HEV', 'Trắng ngọc', 1299000000],
            ]],
            'innova' => ['name' => 'Innova', 'cars' => [
                ['TOY-DEMO-008', 'toyota-innova-2-0g', 'Toyota Innova 2.0G', 'Bạc', 760000000],
            ]],
            'fortuner' => ['name' => 'Fortuner', 'cars' => [
                ['TOY-DEMO-009', 'toyota-fortuner-2-7v', 'Toyota Fortuner 2.7V', 'Đen', 1023000000],
                ['TOY-DEMO-010', 'toyota-fortuner-2-8hev', 'Toyota Fortuner GR-S', 'Xám', 1268000000],
            ]],
            'raize' => ['name' => 'Raize', 'cars' => [
                ['TOY-DEMO-011', 'toyota-raize-1-0t', 'Toyota Raize 1.0 Turbo', 'Xanh', 542000000],
            ]],
            'rush' => ['name' => 'Rush', 'cars' => [
                ['TOY-DEMO-012', 'toyota-rush-1-5g', 'Toyota Rush 1.5G', 'Trắng', 659000000],
            ]],
        ]);

        foreach ($categories as $slug => $category) {
            $model = CarCategory::firstOrCreate(
                ['slug' => $slug],
                ['brand' => 'Toyota', 'name' => $category['name'], 'is_visible' => true]
            );
            foreach ($category['cars'] as [$code, $carSlug, $name, $color, $price]) {
                Car::firstOrCreate(
                    ['code' => $code],
                    [
                        'slug' => $carSlug, 'name' => $name,
                        'category_id' => $model->id, 'status' => CarStatus::Available,
                        'is_visible' => true, 'year' => (int) date('Y'),
                        'color' => $color, 'price' => $price,
                    ]
                );
            }
        }
    }
}
