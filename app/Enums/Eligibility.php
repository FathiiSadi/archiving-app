<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Eligibility: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Eligible = 'eligible';
    case NotEligible = 'not_eligible';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'قيد التقييم',
            self::Eligible => 'مؤهل للانتقال',
            self::NotEligible => 'غير مؤهل',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Eligible => 'success',
            self::NotEligible => 'danger',
        };
    }
}
