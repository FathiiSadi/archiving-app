<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum VendorType: string implements HasColor, HasLabel
{
    case Vendor = 'vendor';
    case Venue = 'venue';

    public function getLabel(): string
    {
        return match ($this) {
            self::Vendor => 'مورّد',
            self::Venue => 'قاعة / مكان',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Vendor => 'primary',
            self::Venue => 'info',
        };
    }
}
