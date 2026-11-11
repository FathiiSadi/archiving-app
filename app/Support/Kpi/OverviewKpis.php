<?php

namespace App\Support\Kpi;

use App\Enums\ArchiveStatus;
use App\Enums\Team;
use App\Models\Activity;
use App\Models\InternalActivity;
use App\Models\QuranCircle;
use App\Models\TajweedSection;

/** Cross-team indicators for initiative management. */
final class OverviewKpis
{
    use CachesResults;

    /** Objective: at least 90% of each semester's records fully archived. */
    public const ARCHIVE_TARGET = 90;

    /** @param  array<int>|null  $semesterIds */
    public function __construct(private readonly ?array $semesterIds) {}

    /** @return array<string, array{team: Team, total: int, complete: int, rate: ?float}> */
    public function archiveByTeam(): array
    {
        $models = [
            Team::Activities->value => Activity::class,
            Team::Quran->value => QuranCircle::class,
            Team::Tajweed->value => TajweedSection::class,
            Team::InternalAffairs->value => InternalActivity::class,
        ];

        $rows = $this->cached(__FUNCTION__, fn () => collect($models)->map(function (string $model, string $team) {
            $total = $model::query()->inSemesters($this->semesterIds)->count();
            $complete = $model::query()->inSemesters($this->semesterIds)->where('archive_status', ArchiveStatus::Complete)->count();

            return ['team' => $team, 'total' => $total, 'complete' => $complete, 'rate' => Kpi::rate($complete, $total)];
        })->all());

        return array_map(fn (array $row) => [...$row, 'team' => Team::from($row['team'])], $rows);
    }

    public function archiveOverall(): ?float
    {
        $rows = collect($this->archiveByTeam());

        return Kpi::rate($rows->sum('complete'), $rows->sum('total'));
    }
}
