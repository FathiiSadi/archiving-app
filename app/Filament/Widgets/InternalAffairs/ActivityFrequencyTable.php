<?php

namespace App\Filament\Widgets\InternalAffairs;

use App\Filament\Widgets\DalTableWidget;
use App\Support\Kpi\InternalAffairsKpis;
use App\Support\Kpi\Kpi;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivityFrequencyTable extends DalTableWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->heading('تكرار الأنشطة الداخلية')
            ->description('عدد مرات تنفيذ كل نشاط من القائمة المعتمدة · '.$this->scopeLabel())
            ->records(fn () => (new InternalAffairsKpis($this->semesterIds()))->frequency()
                ->values()
                ->mapWithKeys(fn ($r, $i) => [$i + 1 => [
                    'name' => $r->name,
                    'runs' => (int) $r->runs,
                    'participants' => Kpi::number($r->participants),
                    'net' => Kpi::money($r->net),
                    'net_positive' => (float) $r->net >= 0,
                    'rating' => $r->rating === null ? '—' : Kpi::ratio(Kpi::number($r->rating, 2), 5),
                ]])->all())
            ->columns([
                TextColumn::make('name')->label('النشاط')->weight('medium'),
                TextColumn::make('runs')->label('مرات التنفيذ')->alignCenter()->badge()->color('gray'),
                TextColumn::make('participants')->label('المشاركون'),
                TextColumn::make('net')->label('الصافي')->color(fn (array $record) => $record['net_positive'] ? 'success' : 'danger'),
                TextColumn::make('rating')->label('متوسط التقييم'),
            ])
            ->paginated(false)
            ->emptyStateHeading('لا توجد أنشطة داخلية في هذا النطاق');
    }
}
