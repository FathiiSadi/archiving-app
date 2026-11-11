<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StaffRole: string implements HasColor, HasLabel
{
    case Instructor = 'instructor';
    case Supervisor = 'supervisor';

    public function getLabel(): string
    {
        return match ($this) {
            self::Instructor => 'مدرّس',
            self::Supervisor => 'مشرف',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Instructor => 'primary',
            self::Supervisor => 'info',
        };
    }
}
