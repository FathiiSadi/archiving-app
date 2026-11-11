<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Team: string implements HasColor, HasLabel
{
    case Activities = 'activities';
    case Quran = 'quran';
    case Tajweed = 'tajweed';
    case InternalAffairs = 'internal_affairs';

    public function getLabel(): string
    {
        return match ($this) {
            self::Activities => 'فريق الأنشطة',
            self::Quran => 'فريق التحفيظ',
            self::Tajweed => 'فريق التجويد',
            self::InternalAffairs => 'فريق الشؤون الداخلية',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Activities => 'primary',
            self::Quran => 'success',
            self::Tajweed => 'info',
            self::InternalAffairs => 'warning',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Activities => 'الأنشطة',
            self::Quran => 'التحفيظ',
            self::Tajweed => 'التجويد',
            self::InternalAffairs => 'الشؤون الداخلية',
        };
    }

    public function leaderRole(): UserRole
    {
        return match ($this) {
            self::Activities => UserRole::ActivitiesLeader,
            self::Quran => UserRole::QuranLeader,
            self::Tajweed => UserRole::TajweedLeader,
            self::InternalAffairs => UserRole::InternalAffairsLeader,
        };
    }

    /** Teams that use the per-semester list of approved activities. */
    public static function withApprovedActivities(): array
    {
        return [self::Activities, self::InternalAffairs];
    }
}
