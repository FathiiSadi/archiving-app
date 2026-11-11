<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\GuardsWidgetAccess;
use App\Filament\Widgets\Concerns\UsesSemesterFilter;
use Filament\Widgets\StatsOverviewWidget;

abstract class DalStatsWidget extends StatsOverviewWidget
{
    use GuardsWidgetAccess;
    use UsesSemesterFilter;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    /** Colour for a rate compared against a target. */
    protected function targetColor(?float $value, float $target): string
    {
        return match (true) {
            $value === null => 'gray',
            $value >= $target => 'success',
            $value >= $target * 0.8 => 'warning',
            default => 'danger',
        };
    }
}
