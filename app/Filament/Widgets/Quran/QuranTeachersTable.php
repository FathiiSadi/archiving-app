<?php

namespace App\Filament\Widgets\Quran;

use App\Filament\Widgets\DalTableWidget;
use App\Support\Kpi\Kpi;
use App\Support\Kpi\QuranKpis;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuranTeachersTable extends DalTableWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->heading('تقييم معلمي التحفيظ ومؤشر الاستقرار')
            ->description('الاستقرار = (الجلسات المخططة − الاعتذارات + التعويض) ÷ الجلسات المخططة · '.$this->scopeLabel())
            ->records(fn () => (new QuranKpis($this->semesterIds()))->teachers()
                ->mapWithKeys(fn ($t) => [$t['teacher_id'] => [
                    'name' => $t['name'],
                    'circles' => $t['circles'],
                    'rating' => $t['rating'] === null ? '—' : Kpi::ratio(Kpi::number($t['rating'], 1), 10),
                    'attendance' => Kpi::percent($t['attendance_rate']),
                    'pages' => Kpi::number($t['pages'], 1),
                    'apology' => Kpi::percent($t['apology_rate']),
                    'compensation' => Kpi::percent($t['compensation_rate']),
                    'stability' => Kpi::percent($t['stability_index']),
                    'stability_raw' => $t['stability_index'],
                ]])->all())
            ->columns([
                TextColumn::make('name')->label('المعلم')->weight('medium'),
                TextColumn::make('circles')->label('الحلقات')->alignCenter(),
                TextColumn::make('rating')->label('التقييم')->badge()->color('primary'),
                TextColumn::make('attendance')->label('حضور الطلاب'),
                TextColumn::make('pages')->label('الصفحات'),
                TextColumn::make('apology')->label('الاعتذار'),
                TextColumn::make('compensation')->label('التعويض'),
                TextColumn::make('stability')->label('الاستقرار')->badge()
                    ->color(fn (array $record) => match (true) {
                        $record['stability_raw'] === null => 'gray',
                        $record['stability_raw'] >= 90 => 'success',
                        $record['stability_raw'] >= 75 => 'warning',
                        default => 'danger',
                    }),
            ])
            ->paginated(false)
            ->emptyStateHeading('لا توجد حلقات في هذا النطاق');
    }
}
