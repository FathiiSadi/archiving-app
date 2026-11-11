<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SessionMode: string implements HasColor, HasLabel
{
    case InPerson = 'in_person';
    case Online = 'online';

    public function getLabel(): string
    {
        return match ($this) {
            self::InPerson => 'حضوري',
            self::Online => 'عن بُعد',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::InPerson => 'primary',
            self::Online => 'info',
        };
    }
}
