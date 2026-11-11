<?php

namespace App\Filament\Support;

use App\Enums\ArchiveStatus;
use App\Models\Semester;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

/** Year / semester filters for every archive table (BR-15). */
final class Filters
{
    /**
     * @param  string|null  $through  relation leading to a model with semester_id (e.g. "circle"), null for direct
     */
    public static function semester(?string $through = null): SelectFilter
    {
        return SelectFilter::make('semester')
            ->label('الفصل الدراسي')
            ->options(fn () => Semester::options())
            ->default(fn () => Semester::current()?->id)
            ->query(function (Builder $query, array $data) use ($through): Builder {
                if (blank($data['value'] ?? null)) {
                    return $query;
                }

                return $through
                    ? $query->whereHas($through, fn (Builder $query) => $query->where('semester_id', $data['value']))
                    : $query->where($query->qualifyColumn('semester_id'), $data['value']);
            });
    }

    public static function academicYear(?string $through = null): SelectFilter
    {
        return SelectFilter::make('academic_year')
            ->label('العام الجامعي')
            ->options(fn () => Semester::yearOptions())
            ->query(function (Builder $query, array $data) use ($through): Builder {
                if (blank($data['value'] ?? null)) {
                    return $query;
                }

                $relation = $through ? "{$through}.semester" : 'semester';

                return $query->whereHas($relation, fn (Builder $query) => $query->where('academic_year', $data['value']));
            });
    }

    public static function archiveStatus(): SelectFilter
    {
        return SelectFilter::make('archive_status')
            ->label('حالة الأرشفة')
            ->options(ArchiveStatus::class);
    }
}
