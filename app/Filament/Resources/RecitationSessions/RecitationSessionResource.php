<?php

namespace App\Filament\Resources\RecitationSessions;

use App\Enums\SessionMode;
use App\Enums\TeacherAttendance;
use App\Filament\Resources\RecitationSessions\Pages\ManageRecitationSessions;
use App\Filament\Support\Filters;
use App\Models\QuranCircle;
use App\Models\RecitationSession;
use App\Models\Semester;
use App\Support\Kpi\Kpi;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class RecitationSessionResource extends Resource
{
    protected static ?string $model = RecitationSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'فريق التحفيظ';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'جلسة تسميع';

    protected static ?string $pluralModelLabel = 'جلسات التسميع';

    private static function held(Get $get): bool
    {
        $value = $get('teacher_attendance');

        return ($value instanceof TeacherAttendance ? $value : TeacherAttendance::tryFrom((string) $value)) !== TeacherAttendance::Apologized;
    }

    public static function formComponents(bool $withCircle = true): array
    {
        return [
            Grid::make(2)->schema([
                ...($withCircle ? [
                    Select::make('quran_circle_id')
                        ->label('الحلقة')
                        ->options(fn () => QuranCircle::query()->where('semester_id', Semester::current()?->id)->orderBy('name')->pluck('name', 'id'))
                        ->getSearchResultsUsing(fn (string $search) => QuranCircle::query()->where('name', 'like', "%{$search}%")->limit(30)->pluck('name', 'id'))
                        ->getOptionLabelUsing(fn ($value) => QuranCircle::find($value)?->name)
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            // Pre-fill the class size from the last session (or the circle's capacity).
                            if (! $state) {
                                return;
                            }
                            $last = RecitationSession::query()->where('quran_circle_id', $state)->latest('held_on')->value('total_students');
                            $set('total_students', $last ?? QuranCircle::find($state)?->capacity);
                        }),
                ] : []),
                DatePicker::make('held_on')->label('تاريخ الجلسة')->native(false)->displayFormat('d/m/Y')->default(now())->required(),
                ToggleButtons::make('teacher_attendance')
                    ->label('حالة الجلسة')
                    ->options(TeacherAttendance::class)
                    ->default(TeacherAttendance::Held)
                    ->inline()->grouped()->required()->live(),
                ToggleButtons::make('mode')
                    ->label('نمط الجلسة')
                    ->options(SessionMode::class)
                    ->default(SessionMode::InPerson)
                    ->inline()->grouped()->required(),
                Toggle::make('is_compensation')
                    ->label('جلسة تعويضية')
                    ->helperText('فعّلها إذا كانت هذه الجلسة تعويضاً عن جلسة اعتذر عنها المعلم.')
                    ->inline(false),
            ]),
            Section::make('الحضور والتسميع')
                ->compact()
                ->visible(fn (Get $get) => self::held($get))
                ->columns(['default' => 2, 'md' => 3])
                ->schema([
                    TextInput::make('duration_minutes')->label('المدة')->suffix('دقيقة')->numeric()->minValue(0)->default(60)->required(),
                    TextInput::make('total_students')->label('عدد الطلاب الكلي')->numeric()->minValue(0)->required()->live(onBlur: true)
                        ->default(fn ($livewire) => method_exists($livewire, 'getOwnerRecord') ? $livewire->getOwnerRecord()->capacity : null),
                    TextInput::make('present_students')->label('الحاضرون فعلياً')->numeric()->minValue(0)->required()->live(onBlur: true)
                        ->lte('total_students')
                        ->validationMessages(['lte' => 'لا يمكن أن يتجاوز الحاضرون العدد الكلي.']),
                    TextInput::make('new_pages')->label('صفحات الحفظ الجديد')->numeric()->minValue(0)->step(0.5)->default(0)->live(onBlur: true),
                    TextInput::make('revision_pages')->label('صفحات المراجعة')->numeric()->minValue(0)->step(0.5)->default(0)->live(onBlur: true),
                    Text::make(function (Get $get): string {
                        $pages = (float) $get('new_pages') + (float) $get('revision_pages');
                        $rate = Kpi::rate((int) $get('present_students'), (int) $get('total_students'));

                        return 'الصفحات المسمّعة: '.Kpi::number($pages, 1).'  ·  نسبة الحضور: '.Kpi::percent($rate);
                    })->color('primary')->weight('medium'),
                ]),
            Textarea::make('notes')->label('ملاحظات')->rows(2),
        ];
    }

    public static function tableColumns(bool $withCircle = true): array
    {
        return [
            TextColumn::make('held_on')->label('التاريخ')->date('d/m/Y')->sortable(),
            ...($withCircle ? [TextColumn::make('circle.name')->label('الحلقة')->searchable()
                ->description(fn (RecitationSession $r) => $r->circle?->teacher?->name)] : []),
            TextColumn::make('teacher_attendance')->label('الحالة')->badge(),
            IconColumn::make('is_compensation')->label('تعويضية')->boolean()->trueIcon(Heroicon::OutlinedArrowPath)->falseIcon(Heroicon::OutlinedMinus)->alignCenter(),
            TextColumn::make('mode')->label('النمط')->badge()->toggleable(),
            TextColumn::make('present_students')->label('الحضور')
                ->formatStateUsing(fn (RecitationSession $r) => Kpi::ratio($r->present_students, $r->total_students))
                ->extraAttributes(['class' => 'tabular-nums']),
            TextColumn::make('new_pages')->label('حفظ جديد')->numeric(decimalPlaces: 1)->toggleable(),
            TextColumn::make('revision_pages')->label('مراجعة')->numeric(decimalPlaces: 1)->toggleable(),
            TextColumn::make('pages_recited')->label('المسمّع')->numeric(decimalPlaces: 1)->weight('medium')
                ->summarize(Sum::make()->label('المجموع')->numeric(decimalPlaces: 1)),
            TextColumn::make('duration_minutes')->label('المدة')->suffix(' د')->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components(self::formComponents());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('circle.teacher'))
            ->defaultSort('held_on', 'desc')
            ->columns(self::tableColumns())
            ->filters([
                Filters::semester('circle'),
                Filters::academicYear('circle'),
                SelectFilter::make('quran_circle_id')->label('الحلقة')->relationship('circle', 'name'),
                SelectFilter::make('teacher_attendance')->label('الحالة')->options(TeacherAttendance::class),
            ])
            ->recordActions([ActionGroup::make([EditAction::make()->modalWidth('3xl'), DeleteAction::make()])])
            ->emptyStateHeading('لا توجد جلسات مسجلة');
    }

    public static function getPages(): array
    {
        return ['index' => ManageRecitationSessions::route('/')];
    }
}
