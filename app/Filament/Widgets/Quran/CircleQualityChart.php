<?php

namespace App\Filament\Widgets\Quran;

use App\Filament\Widgets\DalChartWidget;
use App\Support\Kpi\QuranKpis;

class CircleQualityChart extends DalChartWidget
{
    protected ?string $heading = 'جودة الحلقات حسب المعايير الخمسة';

    protected function getType(): string
    {
        return 'radar';
    }

    public function getDescription(): ?string
    {
        $q = (new QuranKpis($this->semesterIds()))->quality();

        if ($q['overall'] === null) {
            return 'لا توجد تقييمات في '.$this->scopeLabel();
        }

        $parts = ['نقاط القوة: '.implode('، ', $q['strengths'])];

        if ($q['weaknesses']) {
            $parts[] = 'تحتاج إلى تحسين: '.implode('، ', $q['weaknesses']);
        }

        return implode(' · ', $parts);
    }

    protected function getData(): array
    {
        $criteria = (new QuranKpis($this->semesterIds()))->quality()['criteria'];

        return [
            'datasets' => [[
                'label' => 'متوسط التقييم (من 5)',
                'data' => $criteria->map(fn ($c) => $c['score'] ?? 0)->all(),
                'backgroundColor' => $this->color('ink', 0.15),
                'borderColor' => $this->color('ink'),
                'pointBackgroundColor' => $this->color('brass'),
                'pointRadius' => 4,
                'borderWidth' => 2,
            ]],
            'labels' => $criteria->pluck('label')->all(),
        ];
    }

    protected function getOptions(): array
    {
        $options = $this->baseOptions(axes: false);
        $options['plugins']['legend']['display'] = false;
        $options['scales'] = ['x' => ['display' => false], 'y' => ['display' => false], 'r' => [
            'min' => 0, 'max' => 5,
            'ticks' => ['stepSize' => 1, 'backdropColor' => 'transparent'],
            'pointLabels' => ['font' => ['family' => "'Alexandria', sans-serif", 'size' => 12]],
            'grid' => ['color' => 'rgba(120, 113, 108, 0.18)'],
            'angleLines' => ['color' => 'rgba(120, 113, 108, 0.18)'],
        ]];

        return $options;
    }
}
