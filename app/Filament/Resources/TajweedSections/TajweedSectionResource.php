<?php

namespace App\Filament\Resources\TajweedSections;

use App\Enums\StaffRole;
use App\Filament\Resources\TajweedSections\Pages\CreateTajweedSection;
use App\Filament\Resources\TajweedSections\Pages\EditTajweedSection;
use App\Filament\Resources\TajweedSections\Pages\ListTajweedSections;
use App\Filament\Resources\TajweedSections\Pages\ViewTajweedSection;
use App\Filament\Resources\TajweedSections\RelationManagers\EnrollmentsRelationManager;
use App\Filament\Resources\TajweedSections\RelationManagers\LessonsRelationManager;
use App\Filament\Support\Columns;
use App\Filament\Support\Fields;
use App\Filament\Support\Filters;
use App\Models\TajweedSection;
use App\Support\Kpi\Kpi;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TajweedSectionResource extends Resource
{
    protected static ?string $model = TajweedSection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'فريق التجويد';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'شعبة';

    protected static ?string $pluralModelLabel = 'الشعب';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        $staff = fn (?StaffRole $role) => fn (Builder $query) => $query->where('is_active', true)->when($role, fn ($query) => $query->where('role', $role));

        return $schema->columns(2)->components([
            Section::make('بيانات الشعبة')
                ->icon(Heroicon::OutlinedRectangleGroup)
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Fields::semester(),
                    Select::make('tajweed_level_id')->label('المستوى')
                        ->relationship('level', 'name', fn (Builder $query) => $query->orderBy('sort_order'))
                        ->preload()->required(),
                    TextInput::make('name')->label('اسم الشعبة')->required()->maxLength(255),
                    Select::make('instructor_id')->label('المدرّس')
                        ->relationship('instructor', 'name', $staff(StaffRole::Instructor))
                        ->searchable()->preload()->required(),
                    Select::make('supervisor_id')->label('المشرف')
                        ->relationship('supervisor', 'name', $staff(StaffRole::Supervisor))
                        ->searchable()->preload(),
                    TextInput::make('capacity')->label('السعة')->numeric()->minValue(0),
                    TextInput::make('planned_lessons')->label('عدد الدروس المخططة للمستوى')->numeric()->minValue(1)->required()
                        ->helperText('يُستخدم لحساب نسبة إنجاز المستوى مقابل هدف الفصل.'),
                    TextInput::make('schedule')->label('الموعد')->maxLength(255),
                ]),
            Section::make('الأرشفة')
                ->icon(Heroicon::OutlinedArchiveBox)
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Fields::archiveStatus(),
                    Fields::link('drive_folder_url', 'مجلد الشعبة', 'رابط مجلد الكشوفات والاختبارات على Google Drive'),
                    Textarea::make('notes')->label('ملاحظات')->rows(2)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['semester', 'level', 'instructor'])
                ->withCount(['lessons', 'enrollments'])
                ->withSum('lessons', 'present_count')
                ->withSum('lessons', 'enrolled_count'))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('الشعبة')->searchable()->weight('medium')
                    ->description(fn (TajweedSection $r) => $r->level?->name),
                TextColumn::make('instructor.name')->label('المدرّس')->searchable(),
                Columns::semester(),
                TextColumn::make('enrollments_count')->label('الطلاب')->alignCenter(),
                TextColumn::make('lessons_count')->label('إنجاز المستوى')
                    ->formatStateUsing(fn (TajweedSection $r) => Kpi::ratio($r->lessons_count, $r->planned_lessons))
                    ->description(fn (TajweedSection $r) => Kpi::percent(Kpi::rate(min($r->lessons_count, $r->planned_lessons), $r->planned_lessons)))
                    ->extraAttributes(['class' => 'tabular-nums']),
                TextColumn::make('attendance')->label('الالتزام بالحضور')
                    ->state(fn (TajweedSection $r) => Kpi::percent(Kpi::rate($r->lessons_sum_present_count, $r->lessons_sum_enrolled_count))),
                Columns::archiveStatus(),
                Columns::link('drive_folder_url', 'المجلد'),
            ])
            ->filters([
                Filters::semester(),
                Filters::academicYear(),
                SelectFilter::make('tajweed_level_id')->label('المستوى')->relationship('level', 'name'),
                SelectFilter::make('instructor_id')->label('المدرّس')->relationship('instructor', 'name'),
                Filters::archiveStatus(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Columns::previewAction('drive_folder_url', 'معاينة المجلد'),
                    DeleteAction::make(),
                ]),
            ])
            ->emptyStateHeading('لا توجد شعب في هذا الفصل');
    }

    public static function getRelations(): array
    {
        return [LessonsRelationManager::class, EnrollmentsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTajweedSections::route('/'),
            'create' => CreateTajweedSection::route('/create'),
            'view' => ViewTajweedSection::route('/{record}'),
            'edit' => EditTajweedSection::route('/{record}/edit'),
        ];
    }
}
