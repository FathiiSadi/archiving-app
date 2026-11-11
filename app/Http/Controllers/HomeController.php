<?php

namespace App\Http\Controllers;

use App\Models\Semester;
use App\Support\Kpi\ActivitiesKpis;
use App\Support\Kpi\OverviewKpis;
use App\Support\Kpi\QuranKpis;
use App\Support\Kpi\TajweedKpis;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public portal page. It shows only aggregate, non-financial and non-personal
 * figures for the current semester (BR-16): no names, phones or amounts.
 */
class HomeController extends Controller
{
    public function __invoke(): Response
    {
        // Visitors see the Archiving App company page; signed-in users see the portal.
        if (! auth()->check()) {
            return Inertia::render('Landing', [
                'loginUrl' => Filament::getPanel('admin')->getLoginUrl(),
                'contactEmail' => config('app.contact_email'),
            ]);
        }

        $semester = Semester::current();

        $snapshot = $semester
            ? Cache::remember("home.snapshot.{$semester->id}", now()->addMinutes(10), fn () => $this->snapshot($semester))
            : null;

        return Inertia::render('Home', [
            'semester' => $semester?->label,
            'snapshot' => $snapshot,
            'panelUrl' => Filament::getPanel('admin')->getUrl(),
            'loginUrl' => Filament::getPanel('admin')->getLoginUrl(),
            'isSignedIn' => auth()->check(),
            'archiveTarget' => OverviewKpis::ARCHIVE_TARGET,
        ]);
    }

    private function snapshot(Semester $semester): array
    {
        $ids = [$semester->id];
        $activities = (new ActivitiesKpis($ids))->summary();
        $quran = (new QuranKpis($ids))->summary();
        $tajweed = (new TajweedKpis($ids))->summary();
        $overview = new OverviewKpis($ids);

        return [
            'archive' => $overview->archiveOverall(),
            'teams' => collect($overview->archiveByTeam())->map(fn ($row) => [
                'key' => $row['team']->value,
                'name' => $row['team']->getLabel(),
                'rate' => $row['rate'],
                'records' => $row['total'],
            ])->values()->all(),
            'activitiesCompleted' => $activities['completed'],
            'attendanceRate' => $activities['attendance_rate'],
            'pagesRecited' => round($quran['pages_recited']),
            'circles' => $quran['circles'],
            'tajweedCompletion' => $tajweed['completion_rate'],
            'tajweedTarget' => $tajweed['target'],
        ];
    }
}
