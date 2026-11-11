<?php

namespace App\Filament\Widgets\Quran;

use App\Filament\Widgets\DalChartWidget;
use App\Support\Kpi\QuranKpis;
use Carbon\CarbonImmutable;

class QuranPagesChart extends DalChartWidget
{
    protected ?string $heading = 'الصفحات المسمّعة شهرياً';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $rows = (new QuranKpis($this->semesterIds()))->pagesByMonth();

        return [
            'datasets' => [
                ['label' => 'حفظ جديد', 'data' => $rows->pluck('new')->all(), 'backgroundColor' => $this->color('ink'), 'borderRadius' => 4, 'maxBarThickness' => 28],
                ['label' => 'مراجعة', 'data' => $rows->pluck('revision')->all(), 'backgroundColor' => $this->color('brass'), 'borderRadius' => 4, 'maxBarThickness' => 28],
            ],
            'labels' => $rows->map(fn ($r) => CarbonImmutable::createFromFormat('Y-m', $r['month'])->locale('ar')->translatedFormat('F Y'))->all(),
        ];
    }

    protected function getOptions(): array
    {
        $options = $this->baseOptions();
        $options['scales']['x']['stacked'] = true;
        $options['scales']['y']['stacked'] = true;

        return $options;
    }
}
