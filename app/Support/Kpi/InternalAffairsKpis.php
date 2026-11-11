<?php

namespace App\Support\Kpi;

use App\Enums\ArchiveStatus;
use App\Models\InternalActivity;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** BR-14: Internal Affairs indicators. */
final class InternalAffairsKpis
{
    use CachesResults;

    /** @param  array<int>|null  $semesterIds */
    public function __construct(private readonly ?array $semesterIds) {}

    private function activities(): Builder
    {
        return InternalActivity::query()->inSemesters($this->semesterIds);
    }

    public function summary(): array
    {
        return $this->cached(__FUNCTION__, function () {
            $row = $this->activities()->toBase()->selectRaw(
                'count(*) as activities, coalesce(sum(revenue), 0) as revenue, coalesce(sum(actual_cost), 0) as cost,
                 coalesce(sum(net_result), 0) as net, coalesce(sum(participants), 0) as participants'
            )->first();

            $total = (int) $row->activities;

            return [
                'activities' => $total,
                'revenue' => (float) $row->revenue,
                'cost' => (float) $row->cost,
                'net' => (float) $row->net,
                'participants' => (int) $row->participants,
                'avg_cost_per_person' => $row->participants ? round((float) $row->cost / (int) $row->participants, 3) : null,
                'avg_final_rating' => Kpi::avg($this->activities()->whereNotNull('final_rating')->avg('final_rating')),
                'avg_vendor_rating' => Kpi::avg(
                    Vendor::query()->whereIn('id', $this->activities()->whereNotNull('vendor_id')->select('vendor_id'))
                        ->whereNotNull('rating')->avg('rating'),
                    1
                ),
                'archive_rate' => Kpi::rate($this->activities()->where('archive_status', ArchiveStatus::Complete)->count(), $total),
            ];
        });
    }

    /** Revenue, cost and running net result per activity, in date order. */
    public function financialSeries(): Collection
    {
        return $this->cached(__FUNCTION__, function () {
            $running = 0.0;

            return $this->activities()
                ->with('approvedActivity:id,name')
                ->orderBy('held_on')->orderBy('id')
                ->get()
                ->map(function (InternalActivity $a) use (&$running) {
                    $running += (float) $a->net_result;

                    return [
                        'label' => $a->approvedActivity?->name ?? '—',
                        'date' => $a->held_on?->format('Y-m-d'),
                        'revenue' => (float) $a->revenue,
                        'cost' => (float) $a->actual_cost,
                        'cumulative' => round($running, 3),
                    ];
                });
        });
    }

    /** How often each approved activity ran, with its totals. */
    public function frequency(): Collection
    {
        return $this->cached(__FUNCTION__, function () {
            return $this->activities()->toBase()
                ->join('approved_activities', 'approved_activities.id', '=', 'internal_activities.approved_activity_id')
                ->selectRaw('approved_activities.name as name, count(*) as runs, sum(internal_activities.participants) as participants,
                             sum(internal_activities.net_result) as net, avg(internal_activities.final_rating) as rating')
                ->groupBy('approved_activities.name')
                ->orderByDesc('runs')
                ->get();
        });
    }
}
