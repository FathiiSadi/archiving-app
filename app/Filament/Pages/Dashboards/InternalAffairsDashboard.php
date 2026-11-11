<?php

namespace App\Filament\Pages\Dashboards;

use App\Enums\Team;
use App\Filament\Widgets\InternalAffairs\InternalAffairsStats;
use App\Filament\Widgets\InternalAffairs\InternalFinanceChart;
use App\Filament\Widgets\InternalAffairs\ActivityFrequencyTable;
use App\Filament\Widgets\InternalAffairs\VendorWhitelist;
use App\Filament\Widgets\InternalAffairs\VendorBlacklist;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class InternalAffairsDashboard extends BaseDashboard
{
    protected static string $routePath = 'internal-affairs-dashboard';

    protected static ?string $title = 'لوحة الشؤون الداخلية';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->canViewTeam(Team::InternalAffairs);
    }

    public function getWidgets(): array
    {
        return [
            InternalAffairsStats::class,
            InternalFinanceChart::class,
            ActivityFrequencyTable::class,
            VendorWhitelist::class,
            VendorBlacklist::class,
        ];
    }
}
