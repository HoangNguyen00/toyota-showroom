<?php

namespace Database\Factories;

use App\Enums\CarStatus;
use App\Models\Car;
use App\Models\CarCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Car>
 */
class CarFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Toyota '.fake()->unique()->word();
        $code = 'CAR-'.Str::upper(Str::random(8));

        return [
            'category_id' => CarCategory::factory(),
            'code' => $code,
            'slug' => Str::slug($name.' '.$code),
            'name' => $name,
            'year' => fake()->numberBetween(2005, 2026),
            'color' => 'Trắng ngọc trai',
            'specs' => null,
            'price' => fake()->numberBetween(400_000_000, 2_500_000_000),
            'status' => CarStatus::Available,
            'is_visible' => true,
        ];
    }
}
