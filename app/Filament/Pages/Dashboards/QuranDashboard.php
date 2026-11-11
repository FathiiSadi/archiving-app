<?php

namespace App\Filament\Pages\Dashboards;

use App\Enums\Team;
use App\Filament\Widgets\Quran\QuranStats;
use App\Filament\Widgets\Quran\QuranPagesChart;
use App\Filament\Widgets\Quran\CircleQualityChart;
use App\Filament\Widgets\Quran\QuranTeachersTable;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class QuranDashboard extends BaseDashboard
{
    protected static string $routePath = 'quran-dashboard';

    protected static ?string $title = 'لوحة التحفيظ';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->canViewTeam(Team::Quran);
    }

    public function getWidgets(): array
    {
        return [
            QuranStats::class,
            QuranPagesChart::class,
            CircleQualityChart::class,
            QuranTeachersTable::class,
        ];
    }
}
