<?php

namespace App\Filament\Widgets\InternalAffairs;

use App\Filament\Widgets\DalStatsWidget;
use App\Support\Kpi\InternalAffairsKpis;
use App\Support\Kpi\Kpi;
use App\Support\Kpi\OverviewKpis;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InternalAffairsStats extends DalStatsWidget
{
    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $k = (new InternalAffairsKpis($this->semesterIds()))->summary();
        $netColor = $k['net'] >= 0 ? 'success' : 'danger';

        return [
            Stat::make('الإيرادات', Kpi::money($k['revenue']))
                ->description('من '.Kpi::number($k['activities']).' نشاط داخلي')
                ->icon(Heroicon::OutlinedArrowTrendingUp),
            Stat::make('التكاليف الفعلية', Kpi::money($k['cost']))
                ->icon(Heroicon::OutlinedArrowTrendingDown),
            Stat::make($k['net'] >= 0 ? 'صافي الفائض التراكمي' : 'صافي العجز التراكمي', Kpi::money(abs($k['net'])))
                ->description('الإيرادات − التكاليف الفعلية')
                ->color($netColor)
                ->descriptionColor($netColor)
                ->icon(Heroicon::OutlinedScale),
            Stat::make('إجمالي المشاركين', Kpi::number($k['participants']))
                ->icon(Heroicon::OutlinedUserGroup),
            Stat::make('متوسط التكلفة على الفرد', $k['avg_cost_per_person'] === null ? '—' : Kpi::money($k['avg_cost_per_person']))
                ->description('التكاليف الفعلية ÷ المشاركين')
                ->icon(Heroicon::OutlinedCalculator),
            Stat::make('متوسط التقييم النهائي', Kpi::score($k['avg_final_rating'], 5))
                ->icon(Heroicon::OutlinedStar),
            Stat::make('متوسط تقييم المورّدين', $k['avg_vendor_rating'] === null ? '—' : Kpi::ratio(Kpi::number($k['avg_vendor_rating'], 1), 10))
                ->description('للمورّدين المتعامل معهم في هذا النطاق')
                ->icon(Heroicon::OutlinedBuildingStorefront),
            Stat::make('اكتمال الأرشفة', Kpi::percent($k['archive_rate']))
                ->description('الهدف '.OverviewKpis::ARCHIVE_TARGET.'٪ من سجلات الفصل')
                ->descriptionColor($this->targetColor($k['archive_rate'], OverviewKpis::ARCHIVE_TARGET))
                ->icon(Heroicon::OutlinedArchiveBox),
        ];
    }
}
