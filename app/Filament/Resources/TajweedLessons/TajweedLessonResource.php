<?php

namespace App\Filament\Resources\TajweedLessons;

use App\Enums\LessonType;
use App\Filament\Resources\TajweedLessons\Pages\ManageTajweedLessons;
use App\Filament\Support\Columns;
use App\Filament\Support\Fields;
use App\Filament\Support\Filters;
use App\Models\Semester;
use App\Models\TajweedLesson;
use App\Models\TajweedSection;
use App\Support\Kpi\Kpi;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TajweedLessonResource extends Resource
{
    protected static ?string $model = TajweedLesson::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static string|UnitEnum|null $navigationGroup = 'فريق التجويد';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'درس';

    protected static ?string $pluralModelLabel = 'المحاضرات والدروس العملية';

    private static function sectionSize(int|string|null $sectionId): ?int
    {
        if (! $sectionId) {
            return null;
        }

        return TajweedSection::query()->whereKey($sectionId)->withCount('enrollments')->first()?->enrollments_count;
    }

    public static function formComponents(bool $withSection = true): array
    {
        return [
            Grid::make(2)->schema([
                ...($withSection ? [
                    Select::make('tajweed_section_id')
                        ->label('الشعبة')
                        ->options(fn () => TajweedSection::query()->where('semester_id', Semester::current()?->id)->orderBy('name')->pluck('name', 'id'))
                        ->getSearchResultsUsing(fn (string $search) => TajweedSection::query()->where('name', 'like', "%{$search}%")->limit(30)->pluck('name', 'id'))
                        ->getOptionLabelUsing(fn ($value) => TajweedSection::find($value)?->name)
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (?string $state, Set $set) => $set('enrolled_count', self::sectionSize($state))),
                ] : []),
                DatePicker::make('held_on')->label('التاريخ')->native(false)->displayFormat('d/m/Y')->default(now())->required(),
                ToggleButtons::make('type')->label('النوع')->options(LessonType::class)->default(LessonType::Theoretical)
                    ->inline()->grouped()->required()->live(),
                TextInput::make('topic')->label('الموضوع')->maxLength(255),
                TextInput::make('enrolled_count')->label('عدد الطلاب المسجلين')->numeric()->minValue(0)->required()->live(onBlur: true)
                    ->default(fn ($livewire) => method_exists($livewire, 'getOwnerRecord') ? self::sectionSize($livewire->getOwnerRecord()->getKey()) : null),
                TextInput::make('present_count')->label('عدد الحاضرين')->numeric()->minValue(0)->required()->live(onBlur: true)
                    ->lte('enrolled_count')->validationMessages(['lte' => 'لا يمكن أن يتجاوز الحاضرون عدد المسجلين.']),
                Text::make(function (Get $get): string {
                    $enrolled = (int) $get('enrolled_count');
                    $present = (int) $get('present_count');

                    return 'نسبة الحضور: '.Kpi::percent(Kpi::rate($present, $enrolled)).'  ·  نسبة الغياب: '.Kpi::percent(Kpi::rate(max(0, $enrolled - $present), $enrolled));
                })->color('primary')->weight('medium')->columnSpanFull(),
                Fields::rating('interaction_rating', 'تفاعل الطلاب (1–5)'),
                Fields::rating('correction_rating', 'تصحيح التلاوة العملي (1–5)')
                    ->visible(fn (Get $get) => in_array($get('type'), [LessonType::Practical, LessonType::Practical->value], true)),
                Textarea::make('notes')->label('ملاحظات')->rows(2)->columnSpanFull(),
            ]),
        ];
    }

    public static function tableColumns(bool $withSection = true): array
    {
        return [
            TextColumn::make('held_on')->label('التاريخ')->date('d/m/Y')->sortable(),
            ...($withSection ? [TextColumn::make('section.name')->label('الشعبة')->searchable()
                ->description(fn (TajweedLesson $r) => $r->section?->level?->name)] : []),
            TextColumn::make('type')->label('النوع')->badge(),
            TextColumn::make('topic')->label('الموضوع')->wrap()->placeholder('—')->toggleable(),
            TextColumn::make('present_count')->label('الحضور')
                ->formatStateUsing(fn (TajweedLesson $r) => Kpi::ratio($r->present_count, $r->enrolled_count))
                ->description(fn (TajweedLesson $r) => Kpi::percent($r->attendanceRate()))
                ->extraAttributes(['class' => 'tabular-nums']),
            TextColumn::make('absence')->label('الغياب')
                ->state(fn (TajweedLesson $r) => Kpi::percent(Kpi::rate(max(0, $r->enrolled_count - $r->present_count), $r->enrolled_count)))
                ->toggleable(),
            Columns::rating('interaction_rating', 'التفاعل'),
            Columns::rating('correction_rating', 'تصحيح التلاوة'),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components(self::formComponents());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('section.level'))
            ->defaultSort('held_on', 'desc')
            ->columns(self::tableColumns())
            ->filters([
                Filters::semester('section'),
                Filters::academicYear('section'),
                SelectFilter::make('tajweed_section_id')->label('الشعبة')->relationship('section', 'name'),
                SelectFilter::make('type')->label('النوع')->options(LessonType::class),
            ])
            ->recordActions([ActionGroup::make([EditAction::make()->modalWidth('3xl'), DeleteAction::make()])])
            ->emptyStateHeading('لا توجد دروس مسجلة');
    }

    public static function getPages(): array
    {
        return ['index' => ManageTajweedLessons::route('/')];
    }
}
