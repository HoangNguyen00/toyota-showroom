<?php

namespace App\Enums;

enum ServiceType: string
{
    case Warranty = 'warranty';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::Warranty => 'Bảo hành',
            self::Maintenance => 'Bảo dưỡng',
        };
    }
}
