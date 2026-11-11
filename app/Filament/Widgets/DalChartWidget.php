<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\GuardsWidgetAccess;
use App\Filament\Widgets\Concerns\UsesSemesterFilter;
use Filament\Widgets\ChartWidget;

abstract class DalChartWidget extends ChartWidget
{
    use GuardsWidgetAccess;
    use UsesSemesterFilter;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '300px';

    /** One restrained palette for every chart in the system. */
    public const PALETTE = [
        'ink' => '#00432D',      // primary
        'brass' => '#68523B',    // accent
        'mint' => '#03875A',     // secondary
        'slate' => '#2f6690',
        'clay' => '#b4413a',
        'sage' => '#86a893',
        'stone' => '#a8a29e',
        'plum' => '#6d4c7d',
    ];

    protected function color(string $name, float $alpha = 1.0): string
    {
        $hex = ltrim(self::PALETTE[$name], '#');
        [$r, $g, $b] = array_map('hexdec', str_split($hex, 2));

        return $alpha >= 1 ? '#'.$hex : "rgba({$r}, {$g}, {$b}, {$alpha})";
    }

    protected function baseOptions(bool $axes = true): array
    {
        $font = ['family' => "'Alexandria', system-ui, sans-serif", 'size' => 12];

        $options = [
            'locale' => 'ar',
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => ['rtl' => true, 'position' => 'bottom', 'labels' => ['font' => $font, 'usePointStyle' => true, 'boxWidth' => 8, 'padding' => 16]],
                'tooltip' => ['rtl' => true, 'titleFont' => $font, 'bodyFont' => $font, 'padding' => 10, 'cornerRadius' => 8],
            ],
        ];

        if (! $axes) {
            $options['scales'] = ['x' => ['display' => false], 'y' => ['display' => false]];
        }

        if ($axes) {
            $options['scales'] = [
                'x' => ['grid' => ['display' => false], 'ticks' => ['font' => $font], 'reverse' => true],
                'y' => ['beginAtZero' => true, 'grid' => ['color' => 'rgba(120, 113, 108, 0.12)'], 'border' => ['display' => false], 'ticks' => ['font' => $font], 'position' => 'right'],
            ];
        }

        return $options;
    }

    public function getDescription(): ?string
    {
        return $this->scopeLabel();
    }
}
