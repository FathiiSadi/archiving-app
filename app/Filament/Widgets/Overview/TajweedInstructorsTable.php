<?php

namespace App\Filament\Widgets\Overview;

use App\Filament\Widgets\DalTableWidget;
use App\Support\Kpi\Kpi;
use App\Support\Kpi\TajweedKpis;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TajweedInstructorsTable extends DalTableWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->heading('تقييم مدرّسي التجويد')
            ->description('مجمّع من أداء الشعب · '.$this->scopeLabel())
            ->records(fn () => (new TajweedKpis($this->semesterIds()))->sectionRows()
                ->groupBy('instructor')
                ->map(fn ($rows, $name) => [
                    'name' => $name ?: '—',
                    'sections' => $rows->count(),
                    'completion' => Kpi::percent(Kpi::rate($rows->sum(fn ($r) => min($r['held'], $r['planned'])), $rows->sum('planned'))),
                    'attendance' => Kpi::percent($rows->whereNotNull('attendance_rate')->avg('attendance_rate')),
                    'practical' => ($p = $rows->whereNotNull('practical_avg')->avg('practical_avg')) === null ? '—' : Kpi::number($p, 1),
                ])
                ->values()
                ->mapWithKeys(fn ($r, $i) => [$i + 1 => $r])
                ->all())
            ->columns([
                TextColumn::make('name')->label('المدرّس')->weight('medium'),
                TextColumn::make('sections')->label('الشعب')->alignCenter(),
                TextColumn::make('completion')->label('إنجاز المستوى'),
                TextColumn::make('attendance')->label('حضور الطلاب'),
                TextColumn::make('practical')->label('متوسط الإتقان العملي'),
            ])
            ->paginated(false)
            ->emptyStateHeading('لا توجد شعب في هذا النطاق');
    }
}
