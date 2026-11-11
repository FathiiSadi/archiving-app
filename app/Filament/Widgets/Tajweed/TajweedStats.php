<?php

namespace App\Filament\Widgets\Tajweed;

use App\Filament\Widgets\DalStatsWidget;
use App\Support\Kpi\Kpi;
use App\Support\Kpi\OverviewKpis;
use App\Support\Kpi\TajweedKpis;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TajweedStats extends DalStatsWidget
{
    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $k = (new TajweedKpis($this->semesterIds()))->summary();
        $completionColor = $this->targetColor($k['completion_rate'], $k['target']);

        return [
            Stat::make('نسبة إنجاز المستويات', Kpi::percent($k['completion_rate']))
                ->description(($k['completion_rate'] === null ? 'لا توجد شعب مخطط لها' : ($k['meets_target'] ? 'تحقق الهدف' : 'دون الهدف')).' — الهدف '.$k['target'].'٪')
                ->descriptionColor($completionColor)
                ->color($completionColor)
                ->icon(Heroicon::OutlinedFlag),
            Stat::make('الالتزام بالحضور', Kpi::percent($k['attendance_rate']))
                ->description('عبر '.Kpi::number($k['lessons']).' محاضرة ودرس عملي')
                ->icon(Heroicon::OutlinedUserGroup),
            Stat::make('متوسط الإتقان العملي', $k['practical_mastery'] === null ? '—' : Kpi::ratio(Kpi::number($k['practical_mastery'], 1), 100))
                ->description('التسميع النظري '.Kpi::number($k['recitation_avg'], 1).' · الاختبار النهائي '.Kpi::number($k['final_exam_avg'], 1))
                ->icon(Heroicon::OutlinedCheckBadge),
            Stat::make('المؤهلون للانتقال', Kpi::percent($k['eligible_rate']))
                ->description('من الطلاب الذين صدر قرار بشأنهم ('.Kpi::number($k['students']).' طالب مسجّل)')
                ->icon(Heroicon::OutlinedArrowTrendingUp),
            Stat::make('تفاعل الطلاب / تصحيح التلاوة', Kpi::number($k['interaction_avg'], 2).' · '.Kpi::number($k['correction_avg'], 2))
                ->description('متوسط التقييم من 5 للتفاعل الصفي ثم للتصحيح العملي')
                ->icon(Heroicon::OutlinedChatBubbleLeftRight),
            Stat::make('اكتمال الأرشفة', Kpi::percent($k['archive_rate']))
                ->description('الهدف '.OverviewKpis::ARCHIVE_TARGET.'٪ من سجلات الفصل')
                ->descriptionColor($this->targetColor($k['archive_rate'], OverviewKpis::ARCHIVE_TARGET))
                ->icon(Heroicon::OutlinedArchiveBox),
        ];
    }
}
