<?php

namespace App\Filament\Pages\Dashboards;

use App\Enums\Team;
use App\Filament\Widgets\Activities\ActivitiesStats;
use App\Filament\Widgets\Activities\ActivitiesStatusChart;
use App\Filament\Widgets\Activities\ActivitiesAttendanceChart;
use App\Filament\Widgets\Activities\GuestsStatusChart;
use App\Filament\Widgets\Activities\SuppliersTable;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class ActivitiesDashboard extends BaseDashboard
{
    protected static string $routePath = 'activities-dashboard';

    protected static ?string $title = 'لوحة الأنشطة';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->canViewTeam(Team::Activities);
    }

    public function getWidgets(): array
    {
        return [
            ActivitiesStats::class,
            ActivitiesStatusChart::class,
            ActivitiesAttendanceChart::class,
            GuestsStatusChart::class,
            SuppliersTable::class,
        ];
    }
}
