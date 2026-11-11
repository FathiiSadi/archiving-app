<?php

namespace App\Filament\Widgets\Overview;

use App\Filament\Widgets\DalStatsWidget;
use App\Support\Kpi\Kpi;
use App\Support\Kpi\OverviewKpis;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ArchiveCompletenessStats extends DalStatsWidget
{
    protected function getHeading(): ?string
    {
        return 'اكتمال الأرشفة — الهدف '.OverviewKpis::ARCHIVE_TARGET.'٪';
    }

    protected function getDescription(): ?string
    {
        return $this->scopeLabel();
    }

    protected function getColumns(): int
    {
        return 5;
    }

    protected function getStats(): array
    {
        $kpis = new OverviewKpis($this->semesterIds());
        $overall = $kpis->archiveOverall();
        $target = OverviewKpis::ARCHIVE_TARGET;

        $stats = [
            Stat::make('كل الفرق', Kpi::percent($overall))
                ->description($overall === null ? 'لا توجد سجلات' : ($overall >= $target ? 'تحقق الهدف' : 'دون الهدف'))
                ->descriptionColor($this->targetColor($overall, $target))
                ->color($this->targetColor($overall, $target))
                ->icon(Heroicon::OutlinedArchiveBox),
        ];

        foreach ($kpis->archiveByTeam() as $row) {
            $stats[] = Stat::make($row['team']->shortLabel(), Kpi::percent($row['rate']))
                ->description(Kpi::number($row['complete']).' من '.Kpi::number($row['total']).' سجل')
                ->descriptionColor($this->targetColor($row['rate'], $target));
        }

        return $stats;
    }
}
