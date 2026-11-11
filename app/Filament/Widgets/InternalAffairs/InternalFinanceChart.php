<?php

namespace App\Filament\Widgets\InternalAffairs;

use App\Filament\Widgets\DalChartWidget;
use App\Support\Kpi\InternalAffairsKpis;
use Illuminate\Support\Str;

class InternalFinanceChart extends DalChartWidget
{
    protected ?string $heading = 'الإيرادات مقابل التكاليف وصافي الفائض التراكمي';

    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $rows = (new InternalAffairsKpis($this->semesterIds()))->financialSeries();

        return [
            'datasets' => [
                [
                    'type' => 'line',
                    'label' => 'الصافي التراكمي',
                    'data' => $rows->pluck('cumulative')->all(),
                    'borderColor' => $this->color('slate'),
                    'backgroundColor' => $this->color('slate'),
                    'tension' => 0.25,
                    'pointRadius' => 3,
                    'borderWidth' => 2,
                ],
                ['label' => 'الإيرادات', 'data' => $rows->pluck('revenue')->all(), 'backgroundColor' => $this->color('ink'), 'borderRadius' => 4, 'maxBarThickness' => 24],
                ['label' => 'التكاليف', 'data' => $rows->pluck('cost')->all(), 'backgroundColor' => $this->color('brass'), 'borderRadius' => 4, 'maxBarThickness' => 24],
            ],
            'labels' => $rows->map(fn ($r) => Str::limit($r['label'], 16))->all(),
        ];
    }

    protected function getOptions(): array
    {
        $options = $this->baseOptions();
        $options['scales']['y']['beginAtZero'] = false;

        return $options;
    }
}
