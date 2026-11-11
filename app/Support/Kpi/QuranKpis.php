<?php

namespace App\Support\Kpi;

use App\Enums\ArchiveStatus;
use App\Enums\TeacherAttendance;
use App\Models\CircleEvaluation;
use App\Models\QuranCircle;
use App\Models\QuranTeacher;
use App\Models\RecitationSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * BR-08: Quran memorisation indicators.
 *
 * Definitions used across the dashboard:
 *  - attendance commitment  = students present ÷ students expected, over sessions that took place;
 *  - scheduled sessions     = all sessions that are not make-up (compensation) sessions;
 *  - apology rate           = sessions the teacher apologised for ÷ scheduled sessions;
 *  - compensation rate      = make-up sessions held ÷ apologised sessions;
 *  - teacher stability      = (scheduled − apologised + make-ups) ÷ scheduled, capped at 100%.
 */
final class QuranKpis
{
    use CachesResults;

    /** @param  array<int>|null  $semesterIds */
    public function __construct(private readonly ?array $semesterIds) {}

    private function circles(): Builder
    {
        return QuranCircle::query()->inSemesters($this->semesterIds);
    }

    private function sessions(): Builder
    {
        return RecitationSession::query()->whereIn('quran_circle_id', $this->circles()->select('id'));
    }

    private function evaluations(): Builder
    {
        return CircleEvaluation::query()->whereIn('quran_circle_id', $this->circles()->select('id'));
    }

    public function summary(): array
    {
        return $this->cached(__FUNCTION__, function () {
            $held = $this->sessions()->where('teacher_attendance', TeacherAttendance::Held);

            $totals = (clone $held)
                ->toBase()->selectRaw('coalesce(sum(present_students), 0) as present, coalesce(sum(total_students), 0) as expected,
                             coalesce(sum(pages_recited), 0) as pages, coalesce(sum(new_pages), 0) as new_pages,
                             coalesce(sum(revision_pages), 0) as revision_pages, count(*) as sessions')
                ->first();

            $stability = $this->stability($this->sessions());
            $quality = $this->quality();
            $circles = $this->circles()->count();

            return [
                'circles' => $circles,
                'sessions_held' => (int) $totals->sessions,
                'pages_recited' => (float) $totals->pages,
                'new_pages' => (float) $totals->new_pages,
                'revision_pages' => (float) $totals->revision_pages,
                'attendance_rate' => Kpi::rate($totals->present, $totals->expected),
                'quality_score' => $quality['overall'],
                'quality_rate' => $quality['overall'] === null ? null : round($quality['overall'] / 5 * 100, 1),
                'strengths' => $quality['strengths'],
                'weaknesses' => $quality['weaknesses'],
                ...$stability,
                'archive_rate' => Kpi::rate($this->circles()->where('archive_status', ArchiveStatus::Complete)->count(), $circles),
            ];
        });
    }

    /** @return array{scheduled:int, apologized:int, compensations:int, apology_rate:?float, compensation_rate:?float, stability_index:?float} */
    public function stability(Builder $sessions): array
    {
        $row = (clone $sessions)->toBase()->selectRaw(
            'sum(case when is_compensation = false then 1 else 0 end) as scheduled,
             sum(case when teacher_attendance = ? then 1 else 0 end) as apologized,
             sum(case when is_compensation = true and teacher_attendance = ? then 1 else 0 end) as compensations',
            [TeacherAttendance::Apologized->value, TeacherAttendance::Held->value]
        )->first();

        $scheduled = (int) $row->scheduled;
        $apologized = (int) $row->apologized;
        $compensations = (int) $row->compensations;

        return [
            'scheduled' => $scheduled,
            'apologized' => $apologized,
            'compensations' => $compensations,
            'apology_rate' => Kpi::rate($apologized, $scheduled),
            'compensation_rate' => $apologized ? min(100.0, Kpi::rate($compensations, $apologized)) : null,
            'stability_index' => $scheduled ? min(100.0, Kpi::rate($scheduled - $apologized + $compensations, $scheduled)) : null,
        ];
    }

    /** BR-07/BR-08: average per criterion, overall KPI, strengths and weaknesses. */
    public function quality(): array
    {
        return $this->cached(__FUNCTION__, function () {
            $select = collect(CircleEvaluation::CRITERIA)->keys()->map(fn ($c) => "avg({$c}) as avg_{$c}")->implode(', ');
            $row = $this->evaluations()->toBase()->selectRaw($select)->first();

            $criteria = collect(CircleEvaluation::CRITERIA)
                ->map(fn (string $label, string $key) => ['key' => $key, 'label' => $label, 'score' => Kpi::avg($row?->{"avg_{$key}"})])
                ->values();

            $scored = $criteria->whereNotNull('score');

            if ($scored->isEmpty()) {
                return ['criteria' => $criteria, 'overall' => null, 'strengths' => [], 'weaknesses' => []];
            }

            $max = $scored->max('score');
            $min = $scored->min('score');

            return [
                'criteria' => $criteria,
                'overall' => round($scored->avg('score'), 2),
                'strengths' => $scored->where('score', $max)->pluck('label')->all(),
                'weaknesses' => $max === $min ? [] : $scored->where('score', $min)->pluck('label')->all(),
            ];
        });
    }

    /** Pages recited per month, split into new memorisation and revision. */
    public function pagesByMonth(): Collection
    {
        return $this->cached(__FUNCTION__, function () {
            return $this->sessions()
                ->where('teacher_attendance', TeacherAttendance::Held)
                ->orderBy('held_on')
                ->get(['held_on', 'new_pages', 'revision_pages'])
                ->groupBy(fn ($s) => $s->held_on->format('Y-m'))
                ->map(fn (Collection $rows, string $month) => [
                    'month' => $month,
                    'new' => round($rows->sum(fn ($r) => (float) $r->new_pages), 1),
                    'revision' => round($rows->sum(fn ($r) => (float) $r->revision_pages), 1),
                ])
                ->values();
        });
    }

    /** Teacher evaluation: rating (/10), stability, apology and compensation rates. */
    public function teachers(): Collection
    {
        return $this->cached(__FUNCTION__, function () {
            $circles = $this->circles()->with('teacher')->get();

            return $circles->groupBy('quran_teacher_id')->map(function (Collection $teacherCircles) {
                /** @var QuranTeacher $teacher */
                $teacher = $teacherCircles->first()->teacher;
                $sessions = RecitationSession::query()->whereIn('quran_circle_id', $teacherCircles->pluck('id'));
                $held = (clone $sessions)->where('teacher_attendance', TeacherAttendance::Held)
                    ->toBase()->selectRaw('coalesce(sum(present_students), 0) as present, coalesce(sum(total_students), 0) as expected, coalesce(sum(pages_recited), 0) as pages')
                    ->first();

                return [
                    'teacher_id' => $teacher->id,
                    'name' => $teacher->name,
                    'circles' => $teacherCircles->count(),
                    'rating' => Kpi::avg($teacherCircles->whereNotNull('teacher_rating')->avg('teacher_rating'), 1),
                    'attendance_rate' => Kpi::rate($held->present, $held->expected),
                    'pages' => (float) $held->pages,
                    ...$this->stability($sessions),
                ];
            })->sortByDesc(fn ($t) => [$t['rating'] ?? -1, $t['stability_index'] ?? -1])->values();
        });
    }
}
