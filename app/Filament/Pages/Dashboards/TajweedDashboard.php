<?php

namespace App\Filament\Pages\Dashboards;

use App\Enums\Team;
use App\Filament\Widgets\Tajweed\TajweedStats;
use App\Filament\Widgets\Tajweed\LevelCompletionChart;
use App\Filament\Widgets\Tajweed\TajweedAttendanceChart;
use App\Filament\Widgets\Tajweed\TajweedSectionsTable;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class TajweedDashboard extends BaseDashboard
{
    protected static string $routePath = 'tajweed-dashboard';

    protected static ?string $title = 'لوحة التجويد';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->canViewTeam(Team::Tajweed);
    }

    public function getWidgets(): array
    {
        return [
            TajweedStats::class,
            LevelCompletionChart::class,
            TajweedAttendanceChart::class,
            TajweedSectionsTable::class,
        ];
    }
}
