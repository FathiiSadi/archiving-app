<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\GuardsWidgetAccess;
use App\Filament\Widgets\Concerns\UsesSemesterFilter;
use Filament\Widgets\TableWidget;

abstract class DalTableWidget extends TableWidget
{
    use GuardsWidgetAccess;
    use UsesSemesterFilter;

    protected int|string|array $columnSpan = 'full';
}
