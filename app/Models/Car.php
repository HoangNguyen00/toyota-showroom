<?php

namespace App\Models;

use App\Enums\CarStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Car extends Model
{
    use HasFactory;

    protected $fillable = ['category_id', 'code', 'slug', 'name', 'year', 'color', 'specs', 'price', 'status', 'is_visible'];

    protected function casts(): array
    {
        return [
            'specs' => 'array',
            'price' => 'decimal:0',
            'year' => 'integer',
            'status' => CarStatus::class,
            'is_visible' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CarCategory::class, 'category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(CarImage::class);
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Public catalog scope: the car and its category must both be visible.
     * TV4/TV5 reuse this scope instead of copying the visibility rule.
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->where('cars.is_visible', true)
            ->whereHas('category', fn (Builder $category): Builder => $category->where('is_visible', true));
    }

    public function primaryImagePath(): ?string
    {
        $image = $this->relationLoaded('images')
            ? $this->images->firstWhere('is_primary', true) ?? $this->images->first()
            : $this->images()->orderByDesc('is_primary')->orderBy('sort_order')->first();

        return $image?->path;
    }
}
