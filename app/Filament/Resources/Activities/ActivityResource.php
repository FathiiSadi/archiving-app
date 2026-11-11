<?php

namespace App\Filament\Resources\Activities;

use App\Enums\ActivityStatus;
use App\Enums\Team;
use App\Filament\Resources\Activities\Pages\CreateActivity;
use App\Filament\Resources\Activities\Pages\EditActivity;
use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Filament\Resources\Activities\Pages\ViewActivity;
use App\Filament\Resources\Activities\RelationManagers\GuestsRelationManager;
use App\Filament\Resources\Activities\RelationManagers\PurchasesRelationManager;
use App\Filament\Support\Columns;
use App\Filament\Support\Fields;
use App\Filament\Support\Filters;
use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\ApprovedActivity;
use App\Support\Kpi\Kpi;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
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

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'فريق الأنشطة';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'نشاط';

    protected static ?string $pluralModelLabel = 'الأنشطة';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make('بيانات النشاط')
                ->description('اختر النشاط من القائمة المعتمدة للفصل، أو اكتب اسمه.')
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->columnSpan(2)
                ->columns(2)
                ->schema([
                    Fields::semester()
                        ->afterStateUpdated(fn (Set $set) => $set('approved_activity_id', null)),
                    Select::make('approved_activity_id')
                        ->label('من قائمة الأنشطة المعتمدة')
                        ->options(fn (Get $get) => ApprovedActivity::optionsFor(Team::Activities, $get('semester_id')))
                        ->native(false)
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            if ($state && ($approved = ApprovedActivity::find($state))) {
                                $set('name', $approved->name);
                            }
                        })
                        ->helperText('اختياري — يملأ اسم النشاط تلقائياً.'),
                    TextInput::make('name')
                        ->label('اسم النشاط')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Select::make('activity_type_id')
                        ->label('نوع النشاط')
                        ->options(fn () => ActivityType::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                        ->native(false)
                        ->searchable(),
                    DatePicker::make('held_on')
                        ->label('تاريخ التنفيذ')
                        ->native(false)
                        ->displayFormat('d/m/Y'),
                    TextInput::make('location')
                        ->label('المكان')
                        ->maxLength(255),
                    ToggleButtons::make('status')
                        ->label('حالة النشاط')
                        ->options(ActivityStatus::class)
                        ->default(ActivityStatus::Planned)
                        ->inline()
                        ->required()
                        ->live(),
                ]),

            Section::make('الحضور والتقييم')
                ->icon(Heroicon::OutlinedUserGroup)
                ->columnSpan(1)
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('expected_attendance')
                            ->label('الحضور المتوقع')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->live(onBlur: true),
                        TextInput::make('actual_attendance')
                            ->label('الحضور الفعلي')
                            ->numeric()
                            ->minValue(0)
                            ->required(fn (Get $get) => self::isCompleted($get('status')))
                            ->live(onBlur: true),
                    ]),
                    Text::make(function (Get $get): string {
                        $rate = Kpi::rate((int) $get('actual_attendance'), (int) $get('expected_attendance'));

                        return $rate === null ? 'نسبة الحضور: —' : 'نسبة الحضور: '.Kpi::percent($rate);
                    })->color('gray'),
                    Fields::rating('rating', 'تقييم النشاط (1–5)')
                        ->required(fn (Get $get) => self::isCompleted($get('status'))),
                ]),

            Section::make('الأرشفة')
                ->icon(Heroicon::OutlinedArchiveBox)
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Fields::archiveStatus(),
                    Fields::link('drive_folder_url', 'مجلد التوثيق', 'رابط مجلد الصور والتقارير على Google Drive'),
                    Textarea::make('notes')->label('ملاحظات')->rows(3)->columnSpanFull(),
                ]),
        ]);
    }

    private static function isCompleted(mixed $status): bool
    {
        return ($status instanceof ActivityStatus ? $status : ActivityStatus::tryFrom((string) $status)) === ActivityStatus::Completed;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['semester', 'type'])->withSum('purchases', 'total_cost'))
            ->defaultSort('held_on', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('النشاط')
                    ->weight('medium')
                    ->description(fn (Activity $r) => $r->type?->name)
                    ->searchable()
                    ->wrap(),
                Columns::semester(),
                TextColumn::make('held_on')->label('التاريخ')->date('d/m/Y')->sortable()->placeholder('—'),
                TextColumn::make('status')->label('الحالة')->badge()->sortable(),
                TextColumn::make('actual_attendance')
                    ->label('الحضور')
                    ->formatStateUsing(fn (Activity $r) => $r->actual_attendance === null ? null : Kpi::ratio($r->actual_attendance, $r->expected_attendance))
                    ->description(fn (Activity $r) => $r->attendanceRate() === null ? null : Kpi::percent($r->attendanceRate()))
                    ->placeholder('—')
                    ->extraAttributes(['class' => 'tabular-nums']),
                Columns::rating('rating', 'التقييم'),
                Columns::money('purchases_sum_total_cost', 'المصروفات')->placeholder('0 د.أ'),
                Columns::archiveStatus(),
                Columns::link('drive_folder_url', 'التوثيق'),
            ])
            ->filters([
                Filters::semester(),
                Filters::academicYear(),
                SelectFilter::make('status')->label('الحالة')->options(ActivityStatus::class),
                SelectFilter::make('activity_type_id')->label('النوع')->relationship('type', 'name'),
                Filters::archiveStatus(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Columns::previewAction('drive_folder_url', 'معاينة مجلد التوثيق'),
                    DeleteAction::make(),
                ]),
            ])
            ->emptyStateHeading('لا توجد أنشطة في هذا الفصل')
            ->emptyStateDescription('ابدأ بأرشفة أول نشاط — لن يستغرق الأمر أكثر من دقيقتين.');
    }

    public static function getRelations(): array
    {
        return [
            PurchasesRelationManager::class,
            GuestsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
            'create' => CreateActivity::route('/create'),
            'view' => ViewActivity::route('/{record}'),
            'edit' => EditActivity::route('/{record}/edit'),
        ];
    }
}
