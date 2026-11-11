<?php

namespace App\Filament\Widgets\Activities;

use App\Filament\Widgets\DalStatsWidget;
use App\Support\Kpi\ActivitiesKpis;
use App\Support\Kpi\Kpi;
use App\Support\Kpi\OverviewKpis;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ActivitiesStats extends DalStatsWidget
{
    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $k = (new ActivitiesKpis($this->semesterIds()))->summary();

        $capColor = match (true) {
            $k['cap_usage_rate'] === null => 'gray',
            $k['cap_usage_rate'] > 100 => 'danger',
            $k['cap_usage_rate'] >= 85 => 'warning',
            default => 'success',
        };

        return [
            Stat::make('الأنشطة المنفّذة', Kpi::number($k['completed']))
                ->description('من أصل '.Kpi::number($k['total']).' نشاط مسجّل')
                ->icon(Heroicon::OutlinedCalendarDays),
            Stat::make('نسبة الحضور الإجمالية', Kpi::percent($k['attendance_rate']))
                ->description(Kpi::number($k['actual_attendance']).' حاضر من '.Kpi::number($k['expected_attendance']).' متوقع')
                ->icon(Heroicon::OutlinedUserGroup),
            Stat::make('متوسط تقييم الأنشطة', Kpi::score($k['avg_rating'], 5))
                ->description('للأنشطة المقيّمة')
                ->icon(Heroicon::OutlinedStar),
            Stat::make('المصروفات مقابل السقف', Kpi::money($k['expenses']))
                ->description($k['budget_cap'] > 0
                    ? Kpi::percent($k['cap_usage_rate']).' من السقف التقديري ('.Kpi::money($k['budget_cap']).')'
                    : 'لم يُحدد سقف تقديري لهذا النطاق')
                ->descriptionColor($capColor)
                ->color($capColor)
                ->icon(Heroicon::OutlinedBanknotes),
            Stat::make('الالتزام بالميزانية المعتمدة', Kpi::percent($k['within_budget_rate']))
                ->description(Kpi::number($k['purchases']).' عملية شراء موثّقة بفاتورة')
                ->color($this->targetColor($k['within_budget_rate'], 100))
                ->icon(Heroicon::OutlinedReceiptPercent),
            Stat::make('اكتمال الأرشفة', Kpi::percent($k['archive_rate']))
                ->description('الهدف '.OverviewKpis::ARCHIVE_TARGET.'٪ من سجلات الفصل')
                ->descriptionColor($this->targetColor($k['archive_rate'], OverviewKpis::ARCHIVE_TARGET))
                ->icon(Heroicon::OutlinedArchiveBox),
        ];
    }
}
