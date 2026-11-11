<?php

namespace App\Filament\Widgets\Quran;

use App\Filament\Widgets\DalStatsWidget;
use App\Support\Kpi\Kpi;
use App\Support\Kpi\OverviewKpis;
use App\Support\Kpi\QuranKpis;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QuranStats extends DalStatsWidget
{
    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $k = (new QuranKpis($this->semesterIds()))->summary();

        return [
            Stat::make('الصفحات المسمّعة تراكمياً', Kpi::number($k['pages_recited'], 1))
                ->description('حفظ جديد '.Kpi::number($k['new_pages'], 1).' · مراجعة '.Kpi::number($k['revision_pages'], 1))
                ->icon(Heroicon::OutlinedBookOpen),
            Stat::make('متوسط الالتزام بالحضور', Kpi::percent($k['attendance_rate']))
                ->description('عبر '.Kpi::number($k['sessions_held']).' جلسة منعقدة في '.Kpi::number($k['circles']).' حلقة')
                ->icon(Heroicon::OutlinedUserGroup),
            Stat::make('مؤشر جودة الحلقات', Kpi::score($k['quality_score'], 5))
                ->description($k['quality_rate'] === null ? 'لا توجد تقييمات بعد' : 'ما يعادل '.Kpi::percent($k['quality_rate']))
                ->icon(Heroicon::OutlinedSparkles),
            Stat::make('مؤشر استقرار المعلمين', Kpi::percent($k['stability_index']))
                ->description('الجلسات المنعقدة كما خُطط لها مع احتساب التعويض')
                ->color($this->targetColor($k['stability_index'], 90))
                ->descriptionColor($this->targetColor($k['stability_index'], 90))
                ->icon(Heroicon::OutlinedShieldCheck),
            Stat::make('نسبة اعتذار المعلمين', Kpi::percent($k['apology_rate']))
                ->description(Kpi::number($k['apologized']).' اعتذار · نسبة التعويض '.Kpi::percent($k['compensation_rate']))
                ->icon(Heroicon::OutlinedArrowPath),
            Stat::make('اكتمال الأرشفة', Kpi::percent($k['archive_rate']))
                ->description('الهدف '.OverviewKpis::ARCHIVE_TARGET.'٪ من سجلات الفصل')
                ->descriptionColor($this->targetColor($k['archive_rate'], OverviewKpis::ARCHIVE_TARGET))
                ->icon(Heroicon::OutlinedArchiveBox),
        ];
    }
}
