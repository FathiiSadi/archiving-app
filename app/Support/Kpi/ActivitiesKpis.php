<?php

namespace App\Support\Kpi;

use App\Enums\ActivityStatus;
use App\Enums\ArchiveStatus;
use App\Enums\Team;
use App\Models\Activity;
use App\Models\ActivityGuest;
use App\Models\ActivityPurchase;
use App\Models\SemesterBudget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** BR-04: Activities team indicators. */
final class ActivitiesKpis
{
    use CachesResults;

    /** @param  array<int>|null  $semesterIds */
    public function __construct(private readonly ?array $semesterIds) {}

    private function activities(): Builder
    {
        return Activity::query()->inSemesters($this->semesterIds);
    }

    private function purchases(): Builder
    {
        return ActivityPurchase::query()->whereIn('activity_id', $this->activities()->select('id'));
    }

    public function summary(): array
    {
        return $this->cached(__FUNCTION__, function () {
            $total = $this->activities()->count();
            $completed = $this->activities()->where('status', ActivityStatus::Completed)->count();

            $attendance = $this->activities()
                ->where('status', ActivityStatus::Completed)
                ->whereNotNull('actual_attendance')
                ->where('expected_attendance', '>', 0)
                ->toBase()->selectRaw('coalesce(sum(actual_attendance), 0) as actual, coalesce(sum(expected_attendance), 0) as expected')
                ->first();

            $expenses = (float) $this->purchases()->sum('total_cost');
            $purchaseCount = $this->purchases()->count();
            $withinBudget = $this->purchases()->where('within_budget', true)->count();

            $budgets = SemesterBudget::query()
                ->where('team', Team::Activities)
                ->when($this->semesterIds !== null, fn ($q) => $q->whereIn('semester_id', $this->semesterIds))
                ->toBase()->selectRaw('coalesce(sum(budget_cap), 0) as cap, coalesce(sum(approved_budget), 0) as approved')
                ->first();

            return [
                'total' => $total,
                'completed' => $completed,
                'attendance_rate' => Kpi::rate($attendance->actual, $attendance->expected),
                'actual_attendance' => (int) $attendance->actual,
                'expected_attendance' => (int) $attendance->expected,
                'avg_rating' => Kpi::avg($this->activities()->whereNotNull('rating')->avg('rating')),
                'expenses' => $expenses,
                'budget_cap' => (float) $budgets->cap,
                'approved_budget' => (float) $budgets->approved,
                'cap_usage_rate' => Kpi::rate($expenses, (float) $budgets->cap),
                'purchases' => $purchaseCount,
                'within_budget_rate' => Kpi::rate($withinBudget, $purchaseCount),
                'archive_rate' => Kpi::rate($this->activities()->where('archive_status', ArchiveStatus::Complete)->count(), $total),
            ];
        });
    }

    /** @return Collection<string, int> status value => count */
    public function byStatus(): Collection
    {
        return $this->cached(__FUNCTION__, function () {
            return $this->activities()->toBase()
                ->selectRaw('status, count(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status')
                ->map(fn ($v) => (int) $v);
        });
    }

    /** @return Collection<string, int> invitation status value => count */
    public function guestsByStatus(): Collection
    {
        return $this->cached(__FUNCTION__, function () {
            return ActivityGuest::query()->toBase()
                ->whereIn('activity_id', $this->activities()->select('id'))
                ->selectRaw('invitation_status, count(*) as aggregate')
                ->groupBy('invitation_status')
                ->pluck('aggregate', 'invitation_status')
                ->map(fn ($v) => (int) $v);
        });
    }

    /** Expected vs. actual attendance for the most recent completed activities. */
    public function attendanceSeries(int $limit = 12): Collection
    {
        return $this->cached(__FUNCTION__.$limit, function () use ($limit) {
            return $this->activities()
                ->where('status', ActivityStatus::Completed)
                ->orderByDesc('held_on')->orderByDesc('id')
                ->limit($limit)
                ->get(['name', 'held_on', 'expected_attendance', 'actual_attendance'])
                ->reverse()->values()
                ->map(fn (Activity $a) => [
                    'name' => $a->name,
                    'expected_attendance' => $a->expected_attendance,
                    'actual_attendance' => $a->actual_attendance,
                ]);
        });
    }

    /** Supplier ranking: spend, number of purchases and average rating (1–5). */
    public function suppliers(): Collection
    {
        return $this->cached(__FUNCTION__, function () {
            return $this->purchases()->toBase()
                ->selectRaw('supplier, count(*) as purchases, sum(total_cost) as spend, avg(supplier_rating) as rating')
                ->groupBy('supplier')
                ->orderByRaw('avg(supplier_rating) desc nulls last')
                ->get();
        });
    }
}
