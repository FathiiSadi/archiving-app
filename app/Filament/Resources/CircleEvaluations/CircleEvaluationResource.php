<?php

namespace App\Filament\Resources\CircleEvaluations;

use App\Filament\Resources\CircleEvaluations\Pages\ManageCircleEvaluations;
use App\Filament\Support\Columns;
use App\Filament\Support\Fields;
use App\Filament\Support\Filters;
use App\Models\CircleEvaluation;
use App\Models\QuranCircle;
use App\Models\Semester;
use App\Support\Kpi\Kpi;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CircleEvaluationResource extends Resource
{
    protected static ?string $model = CircleEvaluation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|UnitEnum|null $navigationGroup = 'فريق التحفيظ';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'تقييم حلقة';

    protected static ?string $pluralModelLabel = 'تقييم الحلقات';

    public static function formComponents(bool $withCircle = true): array
    {
        $criteria = collect(CircleEvaluation::CRITERIA)
            ->map(fn (string $label, string $key) => Fields::rating($key, $label, required: true)->live())
            ->values()->all();

        return [
            Grid::make(2)->schema([
                ...($withCircle ? [
                    Select::make('quran_circle_id')
                        ->label('الحلقة')
                        ->options(fn () => QuranCircle::query()->where('semester_id', Semester::current()?->id)->orderBy('name')->pluck('name', 'id'))
                        ->getSearchResultsUsing(fn (string $search) => QuranCircle::query()->where('name', 'like', "%{$search}%")->limit(30)->pluck('name', 'id'))
                        ->getOptionLabelUsing(fn ($value) => QuranCircle::find($value)?->name)
                        ->searchable()
                        ->required(),
                ] : []),
                DatePicker::make('evaluated_on')->label('تاريخ التقييم')->native(false)->displayFormat('d/m/Y')->default(now())->required(),
            ]),
            Grid::make(['default' => 1, 'md' => 2])->schema($criteria),
            Text::make(function (Get $get): string {
                $scores = collect(array_keys(CircleEvaluation::CRITERIA))->map(fn ($c) => $get($c))->filter(fn ($v) => filled($v));

                return $scores->isEmpty() ? 'المتوسط: —' : 'المتوسط: '.Kpi::ratio(Kpi::number($scores->avg(), 2), 5);
            })->color('primary')->weight('medium'),
            Textarea::make('notes')->label('ملاحظات المقيّم')->rows(2),
        ];
    }

    public static function tableColumns(bool $withCircle = true): array
    {
        $criteria = collect(CircleEvaluation::CRITERIA)
            ->map(fn (string $label, string $key) => TextColumn::make($key)->label($label)->alignCenter()->toggleable())
            ->values()->all();

        return [
            TextColumn::make('evaluated_on')->label('التاريخ')->date('d/m/Y')->sortable(),
            ...($withCircle ? [TextColumn::make('circle.name')->label('الحلقة')->searchable()] : []),
            ...$criteria,
            Columns::rating('average', 'المتوسط'),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components(self::formComponents());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('circle'))
            ->defaultSort('evaluated_on', 'desc')
            ->columns(self::tableColumns())
            ->filters([
                Filters::semester('circle'),
                Filters::academicYear('circle'),
                SelectFilter::make('quran_circle_id')->label('الحلقة')->relationship('circle', 'name'),
            ])
            ->recordActions([ActionGroup::make([EditAction::make()->modalWidth('3xl'), DeleteAction::make()])])
            ->emptyStateHeading('لا توجد تقييمات');
    }

    public static function getPages(): array
    {
        return ['index' => ManageCircleEvaluations::route('/')];
    }
}
