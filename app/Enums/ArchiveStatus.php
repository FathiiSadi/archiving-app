<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ArchiveStatus: string implements HasColor, HasLabel
{
    case NotArchived = 'not_archived';
    case Incomplete = 'incomplete';
    case Complete = 'complete';

    public function getLabel(): string
    {
        return match ($this) {
            self::NotArchived => 'غير مؤرشف',
            self::Incomplete => 'أرشفة ناقصة',
            self::Complete => 'مؤرشف بالكامل',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::NotArchived => 'danger',
            self::Incomplete => 'warning',
            self::Complete => 'success',
        };
    }
}
