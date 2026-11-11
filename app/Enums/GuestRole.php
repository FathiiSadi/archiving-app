<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum GuestRole: string implements HasColor, HasLabel
{
    case Guest = 'guest';
    case Speaker = 'speaker';

    public function getLabel(): string
    {
        return match ($this) {
            self::Guest => 'ضيف',
            self::Speaker => 'متحدث',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Guest => 'gray',
            self::Speaker => 'primary',
        };
    }
}
