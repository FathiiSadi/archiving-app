<?php

namespace App\Filament\Widgets\Tajweed;

use App\Filament\Widgets\DalTableWidget;
use App\Support\Kpi\Kpi;
use App\Support\Kpi\TajweedKpis;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TajweedSectionsTable extends DalTableWidget
{
    public function table(Table $table): Table
    {
        $kpis = fn () => new TajweedKpis($this->semesterIds());

        return $table
            ->heading('أداء الشعب والمدرّسين')
            ->description('إنجاز المستوى = الدروس المنفذة ÷ الدروس المخططة · '.$this->scopeLabel())
            ->records(function () use ($kpis) {
                $k = $kpis();
                $target = $k->target();

                return $k->sectionRows()
                    ->sortBy([['level_order', 'asc'], ['name', 'asc']])
                    ->mapWithKeys(fn ($s) => [$s['id'] => [
                        'name' => $s['name'],
                        'level' => $s['level'],
                        'instructor' => $s['instructor'],
                        'lessons' => Kpi::ratio($s['held'], $s['planned']),
                        'completion' => Kpi::percent($s['completion_rate']),
                        'completion_ok' => $s['completion_rate'] !== null && $s['completion_rate'] >= $target,
                        'attendance' => Kpi::percent($s['attendance_rate']),
                        'practical' => $s['practical_avg'] === null ? '—' : Kpi::number($s['practical_avg'], 1),
                    ]])->all();
            })
            ->columns([
                TextColumn::make('name')->label('الشعبة')->weight('medium'),
                TextColumn::make('level')->label('المستوى'),
                TextColumn::make('instructor')->label('المدرّس'),
                TextColumn::make('lessons')->label('الدروس'),
                TextColumn::make('completion')->label('الإنجاز')->badge()
                    ->color(fn (array $record) => $record['completion_ok'] ? 'success' : 'warning'),
                TextColumn::make('attendance')->label('الحضور'),
                TextColumn::make('practical')->label('الإتقان العملي'),
            ])
            ->paginated(false)
            ->emptyStateHeading('لا توجد شعب في هذا النطاق');
    }
}
