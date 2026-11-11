<?php

namespace App\Filament\Widgets\Overview;

use App\Filament\Widgets\DalTableWidget;
use App\Models\Semester;
use App\Support\Kpi\ActivitiesKpis;
use App\Support\Kpi\InternalAffairsKpis;
use App\Support\Kpi\Kpi;
use App\Support\Kpi\OverviewKpis;
use App\Support\Kpi\QuranKpis;
use App\Support\Kpi\TajweedKpis;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SemesterComparisonTable extends DalTableWidget
{
    public function table(Table $table): Table
    {
        $year = $this->pageFilters['academic_year'] ?? null;

        return $table
            ->heading('مقارنة الفصول')
            ->description(filled($year) ? "فصول العام الجامعي {$year}" : 'جميع الفصول المسجلة')
            ->records(fn () => Semester::query()
                ->when(filled($year), fn ($query) => $query->where('academic_year', $year))
                ->ordered()
                ->get()
                ->mapWithKeys(fn (Semester $s) => [$s->id => $this->row($s)])
                ->all())
            ->columns([
                TextColumn::make('label')->label('الفصل')->weight('bold'),
                ColumnGroup::make('الأنشطة', [
                    TextColumn::make('activities')->label('المنفّذة'),
                    TextColumn::make('attendance')->label('الحضور'),
                    TextColumn::make('expenses')->label('المصروف / السقف'),
                ]),
                ColumnGroup::make('التحفيظ', [
                    TextColumn::make('pages')->label('الصفحات'),
                    TextColumn::make('quality')->label('الجودة'),
                    TextColumn::make('stability')->label('الاستقرار'),
                ]),
                ColumnGroup::make('التجويد', [
                    TextColumn::make('completion')->label('الإنجاز')->badge()
                        ->color(fn (array $record) => $record['completion_ok'] === null ? 'gray' : ($record['completion_ok'] ? 'success' : 'warning')),
                    TextColumn::make('mastery')->label('الإتقان'),
                ]),
                ColumnGroup::make('الشؤون الداخلية', [
                    TextColumn::make('net')->label('الصافي')
                        ->color(fn (array $record) => $record['net_raw'] >= 0 ? 'success' : 'danger'),
                    TextColumn::make('participants')->label('المشاركون'),
                ]),
                TextColumn::make('archive')->label('الأرشفة')->badge()
                    ->color(fn (array $record) => $record['archive_raw'] === null ? 'gray' : ($record['archive_raw'] >= OverviewKpis::ARCHIVE_TARGET ? 'success' : 'danger')),
            ])
            ->paginated(false)
            ->emptyStateHeading('لا توجد فصول مسجلة');
    }

    private function row(Semester $semester): array
    {
        $ids = [$semester->id];
        $a = (new ActivitiesKpis($ids))->summary();
        $q = (new QuranKpis($ids))->summary();
        $t = (new TajweedKpis($ids))->summary();
        $i = (new InternalAffairsKpis($ids))->summary();
        $archive = (new OverviewKpis($ids))->archiveOverall();

        return [
            'label' => $semester->label,
            'activities' => Kpi::ratio(Kpi::number($a['completed']), Kpi::number($a['total'])),
            'attendance' => Kpi::percent($a['attendance_rate']),
            'expenses' => Kpi::ratio(Kpi::number($a['expenses'], 0), $a['budget_cap'] > 0 ? Kpi::number($a['budget_cap'], 0) : '—'),
            'pages' => Kpi::number($q['pages_recited'], 0),
            'quality' => Kpi::score($q['quality_score'], 5),
            'stability' => Kpi::percent($q['stability_index']),
            'completion' => Kpi::percent($t['completion_rate']),
            'completion_ok' => $t['completion_rate'] === null ? null : $t['meets_target'],
            'mastery' => $t['practical_mastery'] === null ? '—' : Kpi::number($t['practical_mastery'], 1),
            'net' => Kpi::money($i['net']),
            'net_raw' => $i['net'],
            'participants' => Kpi::number($i['participants']),
            'archive' => Kpi::percent($archive),
            'archive_raw' => $archive,
        ];
    }
}
