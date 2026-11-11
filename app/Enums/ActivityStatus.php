<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ActivityStatus: string implements HasColor, HasLabel
{
    case Planned = 'planned';
    case Completed = 'completed';
    case Postponed = 'postponed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Planned => 'مخطط له',
            self::Completed => 'منفّذ',
            self::Postponed => 'مؤجل',
            self::Cancelled => 'ملغى',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Planned => 'gray',
            self::Completed => 'success',
            self::Postponed => 'warning',
            self::Cancelled => 'danger',
        };
    }
}
