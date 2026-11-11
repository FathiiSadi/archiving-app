<?php

namespace App\Filament\Widgets\InternalAffairs;

use App\Filament\Support\Columns;
use App\Filament\Widgets\Concerns\GuardsWidgetAccess;
use App\Models\Vendor;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** Whitelist / blacklist built from vendor ratings and the "suitable for future activities" decision (BR-14). */
abstract class VendorListTable extends TableWidget
{
    use GuardsWidgetAccess;

    protected int|string|array $columnSpan = 1;

    abstract protected function suitable(): bool;

    public function table(Table $table): Table
    {
        $suitable = $this->suitable();

        return $table
            ->heading($suitable ? 'القائمة البيضاء للمورّدين' : 'القائمة السوداء للمورّدين')
            ->description($suitable ? 'صالحون للتعامل في الأنشطة القادمة — مرتبون حسب التقييم' : 'غير صالحين للتعامل مستقبلاً')
            ->query(Vendor::query()->where('suitable_for_future', $suitable)->withCount('internalActivities'))
            ->defaultSort('rating', $suitable ? 'desc' : 'asc')
            ->columns([
                TextColumn::make('name')->label('الاسم')->weight('medium')->description(fn (Vendor $r) => $r->service),
                TextColumn::make('type')->label('النوع')->badge(),
                Columns::rating('rating', 'التقييم', 10),
                TextColumn::make('internal_activities_count')->label('مرات التعامل')->alignCenter(),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('القائمة فارغة');
    }
}
