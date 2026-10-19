<?php

namespace Database\Factories;

use App\Models\CarCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CarCategory>
 */
class CarCategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Toyota '.fake()->unique()->word();

        return [
            'brand' => 'Toyota',
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'description' => null,
            'is_visible' => true,
        ];
    }
}
