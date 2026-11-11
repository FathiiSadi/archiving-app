<?php

namespace App\Filament\Widgets\Tajweed;

use App\Filament\Widgets\DalChartWidget;
use App\Support\Kpi\TajweedKpis;
use Carbon\CarbonImmutable;

class TajweedAttendanceChart extends DalChartWidget
{
    protected ?string $heading = 'نسبة الحضور الشهرية';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $rows = (new TajweedKpis($this->semesterIds()))->attendanceByMonth();
        $line = fn (string $color) => ['borderColor' => $this->color($color), 'backgroundColor' => $this->color($color), 'tension' => 0.3, 'pointRadius' => 3, 'borderWidth' => 2, 'spanGaps' => true];

        return [
            'datasets' => [
                ['label' => 'المحاضرات النظرية', 'data' => $rows->pluck('theoretical')->all(), ...$line('ink')],
                ['label' => 'الدروس العملية', 'data' => $rows->pluck('practical')->all(), ...$line('brass')],
            ],
            'labels' => $rows->map(fn ($r) => CarbonImmutable::createFromFormat('Y-m', $r['month'])->locale('ar')->translatedFormat('F Y'))->all(),
        ];
    }

    protected function getOptions(): array
    {
        $options = $this->baseOptions();
        $options['scales']['y']['max'] = 100;

        return $options;
    }
}
