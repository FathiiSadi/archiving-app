<?php

namespace App\Models;

use App\Enums\Team;
use App\Enums\Term;
use App\Models\Contracts\SettingsRecord;
use App\Support\Kpi\KpiCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;

class Semester extends Model implements SettingsRecord
{
    protected $fillable = ['academic_year', 'term', 'starts_on', 'ends_on', 'tajweed_target_rate', 'is_current', 'notes'];

    protected function casts(): array
    {
        return [
            'term' => Term::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_current' => 'boolean',
            'tajweed_target_rate' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Only one semester can be the current one.
        static::saved(function (Semester $semester): void {
            if ($semester->is_current) {
                static::query()->whereKeyNot($semester->getKey())->where('is_current', true)->update(['is_current' => false]);
            }
        });
    }

    public function getLabelAttribute(): string
    {
        return $this->term->getLabel().' '.$this->academic_year;
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(SemesterBudget::class);
    }

    public function approvedActivities(): HasMany
    {
        return $this->hasMany(ApprovedActivity::class);
    }

    public function budgetFor(Team $team): ?SemesterBudget
    {
        return $this->budgets->firstWhere('team', $team);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('academic_year')
            ->orderByRaw("case term when 'summer' then 3 when 'second' then 2 else 1 end desc");
    }

    public static function current(): ?self
    {
        $rows = static::cachedRows();
        $row = Arr::first($rows, fn (array $row) => (bool) $row['is_current']) ?? Arr::first($rows);

        return $row === null ? null : static::hydrate([$row])->first();
    }

    /** @return array<string, string> id => label, newest first */
    public static function options(): array
    {
        return static::hydrate(static::cachedRows())->mapWithKeys(fn (self $s) => [$s->id => $s->label])->all();
    }

    /** @return array<string, string> */
    public static function yearOptions(): array
    {
        return collect(static::cachedRows())->pluck('academic_year')->unique()->sortDesc()
            ->mapWithKeys(fn (string $year) => [$year => $year])->all();
    }

    /**
     * Semesters are read on almost every page (filters, defaults, dashboards) but change rarely,
     * so their raw rows are cached alongside the indicators and refreshed whenever settings change.
     *
     * @return list<array<string, mixed>> newest first
     */
    protected static function cachedRows(): array
    {
        return KpiCache::remember('semesters:rows', fn () => static::query()->ordered()->get()
            ->map(fn (self $s) => $s->getAttributes())->all());
    }
}
