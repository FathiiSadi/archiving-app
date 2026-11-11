<?php

namespace App\Filament\Resources\QuranCircles;

use App\Filament\Resources\QuranCircles\Pages\CreateQuranCircle;
use App\Filament\Resources\QuranCircles\Pages\EditQuranCircle;
use App\Filament\Resources\QuranCircles\Pages\ListQuranCircles;
use App\Filament\Resources\QuranCircles\Pages\ViewQuranCircle;
use App\Filament\Resources\QuranCircles\RelationManagers\EvaluationsRelationManager;
use App\Filament\Resources\QuranCircles\RelationManagers\SessionsRelationManager;
use App\Filament\Support\Columns;
use App\Filament\Support\Fields;
use App\Filament\Support\Filters;
use App\Models\QuranCircle;
use App\Models\QuranTeacher;
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

class QuranCircleResource extends Resource
{
    protected static ?string $model = QuranCircle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'فريق التحفيظ';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'حلقة';

    protected static ?string $pluralModelLabel = 'الحلقات';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Section::make('بيانات الحلقة')
                ->icon(Heroicon::OutlinedBookOpen)
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Fields::semester(),
                    TextInput::make('name')->label('اسم الحلقة')->required()->maxLength(255),
                    Select::make('quran_teacher_id')
                        ->label('المعلم')
                        ->relationship('teacher', 'name', fn (Builder $query) => $query->where('is_active', true))
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('capacity')->label('السعة (عدد الطلاب)')->numeric()->minValue(0)->required(),
                    Fields::ratingOutOfTen('teacher_rating', 'تقييم المعلم')
                        ->helperText('تقييم أداء المعلم في هذه الحلقة لهذا الفصل.'),
                    TextInput::make('schedule')->label('الموعد')->placeholder('مثال: الأحد والثلاثاء 12:30')->maxLength(255),
                    TextInput::make('location')->label('المكان')->maxLength(255),
                ]),
            Section::make('الأرشفة')
                ->icon(Heroicon::OutlinedArchiveBox)
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Fields::archiveStatus(),
                    Fields::link('drive_folder_url', 'مجلد الحلقة', 'رابط مجلد كشوفات الحلقة على Google Drive'),
                    Textarea::make('notes')->label('ملاحظات')->rows(2)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['semester', 'teacher'])
                ->withSum('sessions', 'pages_recited')
                ->withAvg('evaluations', 'average'))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('الحلقة')->searchable()->weight('medium')
                    ->description(fn (QuranCircle $r) => $r->schedule),
                TextColumn::make('teacher.name')->label('المعلم')->searchable(),
                Columns::semester(),
                TextColumn::make('capacity')->label('السعة')->alignCenter(),
                Columns::rating('teacher_rating', 'تقييم المعلم', 10),
                TextColumn::make('sessions_sum_pages_recited')->label('الصفحات المسمّعة')
                    ->numeric(decimalPlaces: 0)->placeholder('0')->alignCenter(),
                Columns::rating('evaluations_avg_average', 'جودة الحلقة'),
                Columns::archiveStatus(),
                Columns::link('drive_folder_url', 'المجلد'),
            ])
            ->filters([
                Filters::semester(),
                Filters::academicYear(),
                SelectFilter::make('quran_teacher_id')->label('المعلم')->relationship('teacher', 'name'),
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
            ->emptyStateHeading('لا توجد حلقات في هذا الفصل');
    }

    public static function getRelations(): array
    {
        return [SessionsRelationManager::class, EvaluationsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuranCircles::route('/'),
            'create' => CreateQuranCircle::route('/create'),
            'view' => ViewQuranCircle::route('/{record}'),
            'edit' => EditQuranCircle::route('/{record}/edit'),
        ];
    }
}
