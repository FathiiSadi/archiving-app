<?php

namespace App\Filament\Widgets\Tajweed;

use App\Filament\Widgets\DalChartWidget;
use App\Support\Kpi\TajweedKpis;

class LevelCompletionChart extends DalChartWidget
{
    protected ?string $heading = 'إنجاز كل مستوى مقابل هدف الفصل';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $kpis = new TajweedKpis($this->semesterIds());
        $rows = $kpis->completionByLevel();
        $target = $kpis->target();

        return [
            'datasets' => [
                [
                    'type' => 'line',
                    'label' => "الهدف ({$target}٪)",
                    'data' => $rows->map(fn () => $target)->all(),
                    'borderColor' => $this->color('clay'),
                    'borderDash' => [6, 4],
                    'borderWidth' => 2,
                    'pointRadius' => 0,
                    'fill' => false,
                ],
                [
                    'label' => 'نسبة الإنجاز',
                    'data' => $rows->pluck('rate')->all(),
                    'backgroundColor' => $rows->map(fn ($r) => $r['rate'] >= $target ? $this->color('ink') : $this->color('brass'))->all(),
                    'borderRadius' => 4,
                    'maxBarThickness' => 36,
                ],
            ],
            'labels' => $rows->pluck('level')->all(),
        ];
    }

    protected function getOptions(): array
    {
        $options = $this->baseOptions();
        $options['scales']['y']['max'] = 100;

        return $options;
    }
}
