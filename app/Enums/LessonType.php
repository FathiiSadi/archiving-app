<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum LessonType: string implements HasColor, HasLabel
{
    case Theoretical = 'theoretical';
    case Practical = 'practical';

    public function getLabel(): string
    {
        return match ($this) {
            self::Theoretical => 'محاضرة نظرية',
            self::Practical => 'درس عملي',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Theoretical => 'primary',
            self::Practical => 'info',
        };
    }
}
