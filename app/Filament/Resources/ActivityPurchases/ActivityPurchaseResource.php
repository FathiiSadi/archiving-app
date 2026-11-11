<?php

namespace App\Filament\Resources\ActivityPurchases;

use App\Filament\Resources\ActivityPurchases\Pages\ManageActivityPurchases;
use App\Filament\Support\Columns;
use App\Filament\Support\Fields;
use App\Filament\Support\Filters;
use App\Models\Activity;
use App\Models\ActivityPurchase;
use App\Models\Semester;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ActivityPurchaseResource extends Resource
{
    protected static ?string $model = ActivityPurchase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = 'فريق الأنشطة';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'مشترى';

    protected static ?string $pluralModelLabel = 'المشتريات والفواتير';

    /** @return array<\Filament\Schemas\Components\Component> */
    public static function formComponents(bool $withActivity = true): array
    {
        return [
            Grid::make(2)->schema([
                ...($withActivity ? [
                    Select::make('activity_id')
                        ->label('النشاط')
                        ->options(fn () => Activity::query()
                            ->where('semester_id', Semester::current()?->id)
                            ->orderByDesc('held_on')->pluck('name', 'id'))
                        ->getSearchResultsUsing(fn (string $search) => Activity::query()
                            ->where('name', 'like', "%{$search}%")->limit(30)->pluck('name', 'id'))
                        ->getOptionLabelUsing(fn ($value) => Activity::find($value)?->name)
                        ->searchable()
                        ->required()
                        ->columnSpanFull(),
                ] : []),
                TextInput::make('supplier')
                    ->label('المورّد')
                    ->required()
                    ->maxLength(255)
                    ->datalist(fn () => ActivityPurchase::query()->distinct()->orderBy('supplier')->pluck('supplier')->all()),
                DatePicker::make('purchased_on')->label('تاريخ الشراء')->native(false)->displayFormat('d/m/Y')->default(now()),
                TextInput::make('description')->label('الوصف')->required()->maxLength(255)->columnSpanFull(),
                TextInput::make('quantity')->label('الكمية')->numeric()->minValue(0)->default(1)->required(),
                Fields::money('total_cost', 'التكلفة الإجمالية')->required(),
                Toggle::make('within_budget')
                    ->label('ضمن الميزانية المعتمدة')
                    ->default(true)
                    ->inline(false),
                Fields::rating('supplier_rating', 'تقييم المورّد (1–5)'),
                Fields::link('invoice_url', 'رابط صورة الفاتورة', 'رابط صورة الفاتورة على Google Drive')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('notes')->label('ملاحظات')->rows(2)->columnSpanFull(),
            ]),
        ];
    }

    /** @return array<\Filament\Tables\Columns\Column> */
    public static function tableColumns(bool $withActivity = true): array
    {
        return [
            TextColumn::make('purchased_on')->label('التاريخ')->date('d/m/Y')->sortable()->placeholder('—'),
            ...($withActivity ? [TextColumn::make('activity.name')->label('النشاط')->searchable()->wrap()] : []),
            TextColumn::make('supplier')->label('المورّد')->searchable()->weight('medium'),
            TextColumn::make('description')->label('الوصف')->wrap()->toggleable(),
            TextColumn::make('quantity')->label('الكمية')->numeric(decimalPlaces: 0),
            Columns::money('total_cost', 'التكلفة')
                ->summarize(Sum::make()->label('الإجمالي')->numeric(decimalPlaces: 2)->suffix(' د.أ')),
            IconColumn::make('within_budget')->label('ضمن الميزانية')->boolean()->alignCenter(),
            Columns::rating('supplier_rating', 'تقييم المورّد'),
            Columns::link('invoice_url', 'الفاتورة'),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components(self::formComponents());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('activity'))
            ->defaultSort('purchased_on', 'desc')
            ->columns(self::tableColumns())
            ->filters([
                Filters::semester('activity'),
                Filters::academicYear('activity'),
                TernaryFilter::make('within_budget')->label('الالتزام بالميزانية')
                    ->trueLabel('ضمن الميزانية')->falseLabel('خارج الميزانية'),
            ])
            ->recordActions([
                ActionGroup::make([
                    Columns::previewAction('invoice_url', 'معاينة الفاتورة'),
                    EditAction::make()->modalWidth('3xl'),
                    DeleteAction::make(),
                ]),
            ])
            ->emptyStateHeading('لا توجد مشتريات مسجلة');
    }

    public static function getPages(): array
    {
        return ['index' => ManageActivityPurchases::route('/')];
    }
}
