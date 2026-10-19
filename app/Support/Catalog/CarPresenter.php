<?php

namespace App\Support\Catalog;

use App\Enums\CarStatus;
use App\Models\Car;
use Illuminate\Support\Str;

/**
 * Allowlisted view data for public car cards/detail.
 * TV3 hands this shape to the frontend so cards work with both the DB catalog and
 * the local presentation fixtures (resources/data/*.php). No model is serialized directly.
 */
final class CarPresenter
{
    public const STATUS_LABELS = [
        'available' => 'Có sẵn',
        'reserved' => 'Đã giữ chỗ',
        'sold' => 'Đã bán',
    ];

    public static function fromCar(Car $car): array
    {
        $images = $car->images->sortByDesc('is_primary')->sortBy('sort_order')->values();
        $primary = $images->firstWhere('is_primary', true) ?? $images->first();

        return [
            'id' => $car->id,
            'name' => $car->name,
            'category' => $car->category?->slug,
            'category_name' => $car->category?->name,
            'code' => $car->code,
            'year' => $car->year,
            'color' => $car->color,
            'price' => (int) $car->price,
            'status' => $car->status->value,
            'status_label' => self::STATUS_LABELS[$car->status->value] ?? $car->status->value,
            'specs' => $car->specs ?? [],
            'image' => $primary?->url(),
            'image_alt' => $primary?->url() !== null ? $car->name : $car->name,
            'images' => $images->map(fn ($image): array => ['url' => $image->url()])->all(),
            'is_visible' => (bool) $car->is_visible,
        ];
    }

    /**
     * Presentation fixture (collection/detail preview) in the same allowlisted shape.
     */
    public static function fromArray(array $fixture): array
    {
        $status = CarStatus::tryFrom((string) ($fixture['status'] ?? ''))?->value ?? CarStatus::Available->value;
        $image = isset($fixture['image_path'])
            ? asset($fixture['image_path'])
            : asset('assets/collection/'.($fixture['image'] ?? 'corolla.png'));

        return [
            'id' => (int) $fixture['id'],
            'name' => (string) $fixture['name'],
            'category' => $fixture['category'] ?? null,
            'category_name' => Str::after((string) $fixture['name'], 'Toyota '),
            'code' => (string) $fixture['code'],
            'year' => (int) ($fixture['year'] ?? 2026),
            'color' => (string) ($fixture['color'] ?? 'Đang cập nhật'),
            'price' => (int) $fixture['price'],
            'status' => $status,
            'status_label' => self::STATUS_LABELS[$status],
            'specs' => $fixture['specs'] ?? [],
            'image' => $image,
            'image_alt' => $fixture['image_alt'] ?? $fixture['name'],
            'images' => [['url' => $image]],
            'is_visible' => true,
            'fixture' => true,
        ];
    }
}
