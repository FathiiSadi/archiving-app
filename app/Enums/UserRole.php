<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasColor, HasLabel
{
    case Admin = 'admin';
    case Management = 'management';
    case ActivitiesLeader = 'activities_leader';
    case QuranLeader = 'quran_leader';
    case TajweedLeader = 'tajweed_leader';
    case InternalAffairsLeader = 'internal_affairs_leader';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'مدير النظام',
            self::Management => 'إدارة المبادرة',
            self::ActivitiesLeader => 'قائد فريق الأنشطة',
            self::QuranLeader => 'قائد فريق التحفيظ',
            self::TajweedLeader => 'قائد فريق التجويد',
            self::InternalAffairsLeader => 'قائد فريق الشؤون الداخلية',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'danger',
            self::Management => 'primary',
            self::ActivitiesLeader => 'gray',
            self::QuranLeader => 'gray',
            self::TajweedLeader => 'gray',
            self::InternalAffairsLeader => 'gray',
        };
    }

    public function team(): ?Team
    {
        return match ($this) {
            self::ActivitiesLeader => Team::Activities,
            self::QuranLeader => Team::Quran,
            self::TajweedLeader => Team::Tajweed,
            self::InternalAffairsLeader => Team::InternalAffairs,
            default => null,
        };
    }

    public function isLeader(): bool
    {
        return $this->team() !== null;
    }
}
