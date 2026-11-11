<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TeacherAttendance: string implements HasColor, HasLabel
{
    case Held = 'held';
    case Apologized = 'apologized';

    public function getLabel(): string
    {
        return match ($this) {
            self::Held => 'عُقدت',
            self::Apologized => 'اعتذر المعلم',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Held => 'success',
            self::Apologized => 'warning',
        };
    }
}
