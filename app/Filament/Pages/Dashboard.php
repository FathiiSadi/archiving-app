<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Dashboards\ActivitiesDashboard;
use App\Filament\Pages\Dashboards\BaseDashboard;
use App\Filament\Pages\Dashboards\InternalAffairsDashboard;
use App\Filament\Pages\Dashboards\QuranDashboard;
use App\Filament\Pages\Dashboards\TajweedDashboard;
use App\Enums\Team;
use App\Filament\Widgets\Activities\SuppliersTable;
use App\Filament\Widgets\InternalAffairs\VendorBlacklist;
use App\Filament\Widgets\InternalAffairs\VendorWhitelist;
use App\Filament\Widgets\Overview\ArchiveCompletenessStats;
use App\Filament\Widgets\Overview\SemesterComparisonTable;
use App\Filament\Widgets\Overview\TajweedInstructorsTable;
use App\Filament\Widgets\Quran\QuranTeachersTable;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

/**
 * Management overview: archive completeness, semester comparison and the
 * evaluation of teachers and vendors. Team leaders land on their own dashboard.
 */
class Dashboard extends BaseDashboard
{
    protected static string $routePath = '/';

    protected static ?string $title = 'لوحة الإدارة';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static ?int $navigationSort = 0;

    public function mount(): void
    {
        $team = auth()->user()?->team();

        if ($team !== null) {
            $this->redirect(match ($team) {
                Team::Activities => ActivitiesDashboard::getUrl(),
                Team::Quran => QuranDashboard::getUrl(),
                Team::Tajweed => TajweedDashboard::getUrl(),
                Team::InternalAffairs => InternalAffairsDashboard::getUrl(),
            }, navigate: true);
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isAdmin() || $user?->isManagement());
    }

    public function getWidgets(): array
    {
        return [
            ArchiveCompletenessStats::class,
            SemesterComparisonTable::class,
            QuranTeachersTable::class,
            TajweedInstructorsTable::class,
            SuppliersTable::class,
            VendorWhitelist::class,
            VendorBlacklist::class,
        ];
    }
}
