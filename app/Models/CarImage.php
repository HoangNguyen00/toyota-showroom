<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarImage extends Model
{
    protected $fillable = ['car_id', 'path', 'sort_order', 'is_primary'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_primary' => 'boolean',
        ];
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * Public URL for a stored image. Seeded demo images live under public/assets,
     * uploaded images live on the public disk.
     */
    public function url(): string
    {
        return str_starts_with($this->path, 'assets/')
            ? asset($this->path)
            : asset('storage/'.$this->path);
    }
}
