<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Term: string implements HasColor, HasLabel
{
    case First = 'first';
    case Second = 'second';
    case Summer = 'summer';

    public function getLabel(): string
    {
        return match ($this) {
            self::First => 'الفصل الأول',
            self::Second => 'الفصل الثاني',
            self::Summer => 'الفصل الصيفي',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::First => 'primary',
            self::Second => 'info',
            self::Summer => 'warning',
        };
    }
}
