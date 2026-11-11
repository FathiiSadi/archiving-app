<?php

namespace App\Filament\Widgets\Activities;

use App\Filament\Widgets\DalTableWidget;
use App\Support\Kpi\ActivitiesKpis;
use App\Support\Kpi\Kpi;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SuppliersTable extends DalTableWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->heading('تقييم المورّدين — فريق الأنشطة')
            ->description('ترتيب المورّدين حسب متوسط التقييم (1–5) وحجم التعامل · '.$this->scopeLabel())
            ->records(fn () => (new ActivitiesKpis($this->semesterIds()))->suppliers()
                ->values()
                ->mapWithKeys(fn ($r, $i) => [$i + 1 => [
                    'supplier' => $r->supplier,
                    'purchases' => (int) $r->purchases,
                    'spend' => Kpi::money($r->spend),
                    'rating' => $r->rating === null ? '—' : Kpi::ratio(Kpi::number($r->rating, 2), 5),
                ]])->all())
            ->columns([
                TextColumn::make('supplier')->label('المورّد')->weight('medium'),
                TextColumn::make('purchases')->label('عدد المشتريات')->alignCenter(),
                TextColumn::make('spend')->label('إجمالي الإنفاق'),
                TextColumn::make('rating')->label('متوسط التقييم')->badge()->color('primary'),
            ])
            ->paginated(false)
            ->emptyStateHeading('لا توجد مشتريات في هذا النطاق');
    }
}
