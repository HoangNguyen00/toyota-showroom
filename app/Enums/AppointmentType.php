<?php

namespace App\Enums;

enum AppointmentType: string
{
    case Viewing = 'viewing';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::Viewing => 'Xem xe',
            self::Maintenance => 'Bảo dưỡng',
        };
    }
}
