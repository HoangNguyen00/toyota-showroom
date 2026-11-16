<?php

namespace App\Services\Catalog;

use App\Models\Car;
use App\Models\CarCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Creates/updates a car and its images inside one transaction.
 * Status/visibility/price come from validated input; identity fields are server-generated.
 */
class SaveCar
{
    public function create(array $attributes): Car
    {
        return DB::transaction(function () use ($attributes): Car {
            CarCategory::where('brand', 'Toyota')->sharedLock()->findOrFail($attributes['category_id']);

            return Car::create($this->modelAttributes([...$attributes, 'is_visible' => false], null));
        }, 5);
    }

    public function update(Car $car, array $attributes): Car
    {
        return DB::transaction(function () use ($car, $attributes): Car {
            // Match TV4's Category -> Car order; never acquire a category lock after a car lock.
            $categoryIds = array_unique([$car->category_id, (int) $attributes['category_id']]);
            $categories = CarCategory::whereIn('id', $categoryIds)->orderBy('id')->sharedLock()->get()->keyBy('id');
            $locked = Car::whereKey($car->id)->lockForUpdate()->firstOrFail();
            $category = $categories->get($attributes['category_id']);
            if (! in_array($locked->category_id, $categoryIds, true) || ! $category || $category->brand !== 'Toyota') {
                throw ValidationException::withMessages(['category_id' => 'Dòng xe vừa thay đổi. Vui lòng tải lại và thử lại.']);
            }
            if ($attributes['is_visible'] ?? false) {
                if (! $category->is_visible || $locked->images()->where('is_primary', true)->count() !== 1) {
                    throw ValidationException::withMessages(['is_visible' => 'Cần dòng xe hiển thị và đúng một ảnh chính trước khi công khai.']);
                }
            }
            $locked->fill($this->modelAttributes($attributes, $locked))->save();

            return $locked;
        }, 5);
    }

    /**
     * Cars with history (inquiry/appointment) are blocked by restrictOnDelete; the
     * controller checks the relations first so the admin gets a validation message.
     */
    public function delete(Car $car): bool
    {
        return DB::transaction(function () use ($car): bool {
            $locked = Car::whereKey($car->id)->lockForUpdate()->first();
            if (! $locked) {
                return false;
            }
            if ($locked->inquiries()->exists() || $locked->appointments()->exists()) {
                return false;
            }

            $locked->delete();

            return true;
        });
    }

    private function modelAttributes(array $attributes, ?Car $car): array
    {
        $attributes['slug'] = $this->slug($attributes['name'], $attributes['code'], $car);
        $attributes['specs'] = $this->specs($attributes['specs'] ?? []);
        $attributes['is_visible'] = (bool) ($attributes['is_visible'] ?? false);

        return $attributes;
    }

    private function slug(string $name, string $code, ?Car $car): string
    {
        $base = Str::limit(Str::slug($name.' '.$code), 200, '');
        $slug = $base;
        $suffix = 1;

        while (Car::where('slug', $slug)->when($car, fn ($query) => $query->whereKeyNot($car->id))->exists()) {
            $slug = Str::limit($base, 200 - strlen((string) $suffix) - 1, '').'-'.(++$suffix);
        }

        return $slug;
    }

    private function specs(array $specs): ?array
    {
        $clean = collect($specs)
            ->filter(fn ($row): bool => is_array($row) && filled($row['label'] ?? null) && filled($row['value'] ?? null))
            ->map(fn (array $row): array => ['label' => trim((string) $row['label']), 'value' => trim((string) $row['value'])])
            ->values()
            ->all();

        return $clean === [] ? null : $clean;
    }
}
