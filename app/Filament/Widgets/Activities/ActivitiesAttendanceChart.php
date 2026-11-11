<?php

namespace App\Filament\Widgets\Activities;

use App\Filament\Widgets\DalChartWidget;
use App\Support\Kpi\ActivitiesKpis;
use Illuminate\Support\Str;

class ActivitiesAttendanceChart extends DalChartWidget
{
    protected ?string $heading = 'الحضور المتوقع مقابل الفعلي';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $rows = (new ActivitiesKpis($this->semesterIds()))->attendanceSeries();

        return [
            'datasets' => [
                ['label' => 'المتوقع', 'data' => $rows->pluck('expected_attendance')->all(), 'backgroundColor' => $this->color('stone', 0.45), 'borderRadius' => 4, 'maxBarThickness' => 22],
                ['label' => 'الفعلي', 'data' => $rows->pluck('actual_attendance')->all(), 'backgroundColor' => $this->color('ink'), 'borderRadius' => 4, 'maxBarThickness' => 22],
            ],
            'labels' => $rows->map(fn ($r) => Str::limit($r['name'], 18))->all(),
        ];
    }

    protected function getOptions(): array
    {
        return $this->baseOptions();
    }
}
