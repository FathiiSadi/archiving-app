<?php

namespace App\Support\Kpi;

use App\Enums\ArchiveStatus;
use App\Enums\Eligibility;
use App\Models\Semester;
use App\Models\TajweedEnrollment;
use App\Models\TajweedLesson;
use App\Models\TajweedSection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * BR-11: Tajweed indicators.
 *
 *  - level completion rate = lessons delivered ÷ lessons planned for each section (capped per section),
 *    compared with the semester target (minimum 70%, editable per semester);
 *  - attendance commitment = students present ÷ students enrolled, across all lessons;
 *  - practical mastery     = average practical score (/100) recorded for students;
 *  - advancement rate      = students marked eligible ÷ students with a decision (BR-10).
 */
final class TajweedKpis
{
    use CachesResults;

    public const DEFAULT_TARGET = 70;

    /** @param  array<int>|null  $semesterIds */
    public function __construct(private readonly ?array $semesterIds) {}

    private function sections(): Builder
    {
        return TajweedSection::query()->inSemesters($this->semesterIds);
    }

    private function lessons(): Builder
    {
        return TajweedLesson::query()->whereIn('tajweed_section_id', $this->sections()->select('id'));
    }

    private function enrollments(): Builder
    {
        return TajweedEnrollment::query()->whereIn('tajweed_section_id', $this->sections()->select('id'));
    }

    public function target(): int
    {
        return $this->cached(__FUNCTION__, function () {
            $avg = Semester::query()
                ->when($this->semesterIds !== null, fn ($q) => $q->whereIn('id', $this->semesterIds))
                ->avg('tajweed_target_rate');

            return $avg === null ? self::DEFAULT_TARGET : (int) round((float) $avg);
        });
    }

    public function summary(): array
    {
        return $this->cached(__FUNCTION__, function () {
            $sections = $this->sectionRows();
            $planned = $sections->sum('planned');
            $delivered = $sections->sum(fn ($s) => min($s['held'], $s['planned']));

            $attendance = $this->lessons()
                ->toBase()->selectRaw('coalesce(sum(present_count), 0) as present, coalesce(sum(enrolled_count), 0) as enrolled')
                ->first();

            $decided = $this->enrollments()->whereIn('eligibility', [Eligibility::Eligible, Eligibility::NotEligible])->count();
            $eligible = $this->enrollments()->where('eligibility', Eligibility::Eligible)->count();
            $completion = Kpi::rate($delivered, $planned);
            $target = $this->target();
            $sectionCount = $sections->count();

            return [
                'sections' => $sectionCount,
                'students' => $this->enrollments()->count(),
                'lessons' => $this->lessons()->count(),
                'completion_rate' => $completion,
                'target' => $target,
                'meets_target' => $completion !== null && $completion >= $target,
                'attendance_rate' => Kpi::rate($attendance->present, $attendance->enrolled),
                'practical_mastery' => Kpi::avg($this->enrollments()->whereNotNull('practical_score')->avg('practical_score'), 1),
                'recitation_avg' => Kpi::avg($this->enrollments()->whereNotNull('recitation_score')->avg('recitation_score'), 1),
                'final_exam_avg' => Kpi::avg($this->enrollments()->whereNotNull('final_exam_score')->avg('final_exam_score'), 1),
                'eligible_rate' => Kpi::rate($eligible, $decided),
                'interaction_avg' => Kpi::avg($this->lessons()->whereNotNull('interaction_rating')->avg('interaction_rating')),
                'correction_avg' => Kpi::avg($this->lessons()->whereNotNull('correction_rating')->avg('correction_rating')),
                'archive_rate' => Kpi::rate($this->sections()->where('archive_status', ArchiveStatus::Complete)->count(), $sectionCount),
            ];
        });
    }

    /** One row per section with its delivery, attendance and mastery figures. */
    public function sectionRows(): Collection
    {
        return $this->cached(__FUNCTION__, function () {
            return $this->sections()
                ->with(['level', 'instructor'])
                ->withCount('lessons')
                ->withSum('lessons as present_sum', 'present_count')
                ->withSum('lessons as enrolled_sum', 'enrolled_count')
                ->withAvg('enrollments as practical_avg', 'practical_score')
                ->get()
                ->map(fn (TajweedSection $s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'level' => $s->level?->name,
                    'level_order' => $s->level?->sort_order ?? 0,
                    'instructor' => $s->instructor?->name,
                    'planned' => (int) $s->planned_lessons,
                    'held' => (int) $s->lessons_count,
                    'completion_rate' => Kpi::rate(min($s->lessons_count, $s->planned_lessons), $s->planned_lessons),
                    'attendance_rate' => Kpi::rate($s->present_sum, $s->enrolled_sum),
                    'practical_avg' => Kpi::avg($s->practical_avg, 1),
                ]);
        });
    }

    /** Completion rate per level, for the chart against the target. */
    public function completionByLevel(): Collection
    {
        return $this->cached(__FUNCTION__, function () {
            return $this->sectionRows()
                ->groupBy('level')
                ->map(fn (Collection $rows, string $level) => [
                    'level' => $level,
                    'order' => $rows->first()['level_order'],
                    'rate' => Kpi::rate($rows->sum(fn ($r) => min($r['held'], $r['planned'])), $rows->sum('planned')) ?? 0,
                ])
                ->sortBy('order')
                ->values();
        });
    }

    /** Monthly attendance rate split by lesson type. */
    public function attendanceByMonth(): Collection
    {
        return $this->cached(__FUNCTION__, function () {
            return $this->lessons()
                ->orderBy('held_on')
                ->get(['held_on', 'type', 'present_count', 'enrolled_count'])
                ->groupBy(fn ($l) => $l->held_on->format('Y-m'))
                ->map(function (Collection $rows, string $month) {
                    $rate = fn (Collection $set) => Kpi::rate($set->sum('present_count'), $set->sum('enrolled_count'));

                    return [
                        'month' => $month,
                        'theoretical' => $rate($rows->where('type', \App\Enums\LessonType::Theoretical)),
                        'practical' => $rate($rows->where('type', \App\Enums\LessonType::Practical)),
                    ];
                })
                ->values();
        });
    }
}
