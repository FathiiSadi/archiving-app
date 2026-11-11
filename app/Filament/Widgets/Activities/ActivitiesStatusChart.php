<?php

namespace App\Filament\Widgets\Activities;

use App\Enums\ActivityStatus;
use App\Filament\Widgets\DalChartWidget;
use App\Support\Kpi\ActivitiesKpis;

class ActivitiesStatusChart extends DalChartWidget
{
    protected ?string $heading = 'الأنشطة حسب الحالة';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $counts = (new ActivitiesKpis($this->semesterIds()))->byStatus();
        $colors = [
            ActivityStatus::Completed->value => 'ink',
            ActivityStatus::Planned->value => 'stone',
            ActivityStatus::Postponed->value => 'brass',
            ActivityStatus::Cancelled->value => 'clay',
        ];

        $statuses = collect(ActivityStatus::cases());

        return [
            'datasets' => [[
                'data' => $statuses->map(fn ($s) => $counts[$s->value] ?? 0)->all(),
                'backgroundColor' => $statuses->map(fn ($s) => $this->color($colors[$s->value]))->all(),
                'borderWidth' => 0,
            ]],
            'labels' => $statuses->map->getLabel()->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [...$this->baseOptions(axes: false), 'cutout' => '68%'];
    }
}
