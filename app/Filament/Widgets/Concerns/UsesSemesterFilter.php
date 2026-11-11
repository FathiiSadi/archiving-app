<?php

namespace App\Filament\Widgets\Concerns;

use App\Support\SemesterScope;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

trait UsesSemesterFilter
{
    use InteractsWithPageFilters;

    /** @return array<int>|null */
    protected function semesterIds(): ?array
    {
        return SemesterScope::ids($this->pageFilters);
    }

    protected function scopeLabel(): string
    {
        return SemesterScope::describe($this->pageFilters);
    }
}
