<?php

namespace App\Filament\Resources\TajweedEnrollments;

use App\Enums\Eligibility;
use App\Enums\Team;
use App\Filament\Resources\TajweedEnrollments\Pages\ManageTajweedEnrollments;
use App\Filament\Resources\TajweedStudents\TajweedStudentResource;
use App\Filament\Support\Fields;
use App\Filament\Support\Filters;
use App\Models\Semester;
use App\Models\TajweedEnrollment;
use App\Models\TajweedSection;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Average;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

class TajweedEnrollmentResource extends Resource
{
    protected static ?string $model = TajweedEnrollment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'فريق التجويد';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'نتيجة طالب';

    protected static ?string $pluralModelLabel = 'النتائج والتأهيل';

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
                        ->live(),
                ] : []),
                Select::make('tajweed_student_id')
                    ->label('الطالب')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, $get, $livewire) => $rule->where(
                            'tajweed_section_id',
                            $get('tajweed_section_id') ?? (method_exists($livewire, 'getOwnerRecord') ? $livewire->getOwnerRecord()->getKey() : null)
                        ),
                    )
                    ->validationMessages(['unique' => 'هذا الطالب مسجل في الشعبة مسبقاً.'])
                    ->createOptionForm(TajweedStudentResource::formComponents())
                    ->createOptionModalHeading('إضافة طالب جديد'),
            ]),
            Grid::make(['default' => 1, 'md' => 3])->schema([
                Fields::score('recitation_score', 'درجة التسميع النظري'),
                Fields::score('practical_score', 'درجة الإتقان العملي'),
                Fields::score('final_exam_score', 'درجة الاختبار النهائي'),
            ]),
            ToggleButtons::make('eligibility')
                ->label('الأهلية للانتقال للمستوى التالي')
                ->options(Eligibility::class)
                ->default(Eligibility::Pending)
                ->inline()
                ->required(),
            Textarea::make('notes')->label('ملاحظات')->rows(2),
        ];
    }

    public static function tableColumns(bool $withSection = true): array
    {
        $avg = fn () => Average::make()->label('المتوسط')->numeric(decimalPlaces: 1);

        return [
            TextColumn::make('student.name')->label('الطالب')->searchable()->weight('medium')
                ->description(fn (TajweedEnrollment $r) => $r->student?->student_number),
            ...($withSection ? [TextColumn::make('section.name')->label('الشعبة')->searchable()
                ->description(fn (TajweedEnrollment $r) => $r->section?->level?->name)] : []),
            TextColumn::make('recitation_score')->label('التسميع النظري')->numeric(decimalPlaces: 1)->placeholder('—')->alignCenter()->summarize($avg()),
            TextColumn::make('practical_score')->label('الإتقان العملي')->numeric(decimalPlaces: 1)->placeholder('—')->alignCenter()->summarize($avg()),
            TextColumn::make('final_exam_score')->label('الاختبار النهائي')->numeric(decimalPlaces: 1)->placeholder('—')->alignCenter()->summarize($avg()),
            TextColumn::make('eligibility')->label('الأهلية')->badge()->sortable(),
        ];
    }

    public static function eligibilityBulkAction(): BulkAction
    {
        return BulkAction::make('setEligibility')
            ->label('تحديد الأهلية')
            ->icon(Heroicon::OutlinedCheckBadge)
            ->schema([
                ToggleButtons::make('eligibility')->label('الأهلية')->options(Eligibility::class)->inline()->required(),
            ])
            ->action(fn (Collection $records, array $data) => $records->each->update(['eligibility' => $data['eligibility']]))
            ->visible(fn () => (bool) auth()->user()?->canManageTeam(Team::Tajweed))
            ->deselectRecordsAfterCompletion();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components(self::formComponents());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['student', 'section.level']))
            ->defaultSort('created_at', 'desc')
            ->columns(self::tableColumns())
            ->filters([
                Filters::semester('section'),
                Filters::academicYear('section'),
                SelectFilter::make('tajweed_section_id')->label('الشعبة')->relationship('section', 'name'),
                SelectFilter::make('eligibility')->label('الأهلية')->options(Eligibility::class),
            ])
            ->recordActions([ActionGroup::make([EditAction::make()->modalWidth('3xl'), DeleteAction::make()])])
            ->toolbarActions([BulkActionGroup::make([self::eligibilityBulkAction()])])
            ->emptyStateHeading('لا توجد نتائج مسجلة');
    }

    public static function getPages(): array
    {
        return ['index' => ManageTajweedEnrollments::route('/')];
    }
}
