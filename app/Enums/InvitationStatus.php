<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvitationStatus: string implements HasColor, HasLabel
{
    case Invited = 'invited';
    case Approved = 'approved';
    case RejectedByDeanship = 'rejected_by_deanship';
    case Apologized = 'apologized';
    case Attended = 'attended';
    case NotAttended = 'not_attended';

    public function getLabel(): string
    {
        return match ($this) {
            self::Invited => 'تمت دعوته',
            self::Approved => 'تمت الموافقة',
            self::RejectedByDeanship => 'رفض من العمادة',
            self::Apologized => 'اعتذر الضيف',
            self::Attended => 'حضر',
            self::NotAttended => 'لم يحضر',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Invited => 'gray',
            self::Approved => 'info',
            self::RejectedByDeanship => 'danger',
            self::Apologized => 'warning',
            self::Attended => 'success',
            self::NotAttended => 'danger',
        };
    }
}
