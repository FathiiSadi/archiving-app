<?php

namespace App\Models\Concerns;

use App\Models\Semester;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait InSemester
{
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    /** @param  array<int>|null  $semesterIds  null means "all semesters" */
    public function scopeInSemesters(Builder $query, ?array $semesterIds): Builder
    {
        return $semesterIds === null ? $query : $query->whereIn($this->qualifyColumn('semester_id'), $semesterIds);
    }
}
