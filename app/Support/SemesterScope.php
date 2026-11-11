<?php

namespace App\Support;

use App\Models\Semester;

/**
 * Turns the dashboard "year / semester" filter (BR-15) into a set of semester ids.
 * A null result means "no restriction" (all semesters).
 */
final class SemesterScope
{
    /** @param  array<string, mixed>|null  $filters */
    public static function ids(?array $filters): ?array
    {
        $year = $filters['academic_year'] ?? null;
        $term = $filters['term'] ?? null;

        if (blank($year) && blank($term)) {
            return null;
        }

        return Semester::query()
            ->when(filled($year), fn ($q) => $q->where('academic_year', $year))
            ->when(filled($term), fn ($q) => $q->where('term', $term))
            ->pluck('id')
            ->all();
    }

    /** @return array{academic_year: ?string, term: ?string} */
    public static function defaults(): array
    {
        $current = Semester::current();

        return [
            'academic_year' => $current?->academic_year,
            'term' => $current?->term->value,
        ];
    }

    /** @param  array<string, mixed>|null  $filters */
    public static function describe(?array $filters): string
    {
        $year = $filters['academic_year'] ?? null;
        $term = filled($filters['term'] ?? null) ? \App\Enums\Term::from($filters['term'])->getLabel() : null;

        return match (true) {
            $year && $term => "{$term} {$year}",
            (bool) $year => "العام الجامعي {$year}",
            (bool) $term => "{$term} — كل الأعوام",
            default => 'كل الفصول والأعوام',
        };
    }
}
