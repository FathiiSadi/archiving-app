<?php

namespace App\Filament\Resources\ActivityGuests;

use App\Enums\GuestRole;
use App\Enums\InvitationStatus;
use App\Enums\Team;
use App\Filament\Resources\ActivityGuests\Pages\ManageActivityGuests;
use App\Filament\Support\Columns;
use App\Filament\Support\Fields;
use App\Filament\Support\Filters;
use App\Models\Activity;
use App\Models\ActivityGuest;
use App\Models\Semester;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ActivityGuestResource extends Resource
{
    protected static ?string $model = ActivityGuest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMicrophone;

    protected static string|UnitEnum|null $navigationGroup = 'فريق الأنشطة';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'ضيف';

    protected static ?string $pluralModelLabel = 'الضيوف والمتحدثون';

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
                TextInput::make('name')->label('الاسم')->required()->maxLength(255),
                ToggleButtons::make('role')->label('الصفة')->options(GuestRole::class)->default(GuestRole::Guest)->inline()->grouped()->required(),
                Fields::gender(),
                Fields::phone(),
                TextInput::make('affiliation')->label('الجهة / المسمى')->maxLength(255),
                TextInput::make('topic')->label('موضوع المشاركة')->maxLength(255),
                ToggleButtons::make('invitation_status')
                    ->label('حالة الدعوة')
                    ->options(InvitationStatus::class)
                    ->default(InvitationStatus::Invited)
                    ->inline()
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('notes')->label('ملاحظات')->rows(2)->columnSpanFull(),
            ]),
        ];
    }

    public static function tableColumns(bool $withActivity = true): array
    {
        return [
            TextColumn::make('name')->label('الاسم')->searchable()->weight('medium')
                ->description(fn (ActivityGuest $r) => $r->affiliation),
            ...($withActivity ? [TextColumn::make('activity.name')->label('النشاط')->searchable()->wrap()] : []),
            TextColumn::make('role')->label('الصفة')->badge(),
            TextColumn::make('gender')->label('الجنس')->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('topic')->label('الموضوع')->wrap()->toggleable()->placeholder('—'),
            TextColumn::make('invitation_status')->label('حالة الدعوة')->badge()->sortable(),
            Columns::phone(Team::Activities),
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
            ->defaultSort('created_at', 'desc')
            ->columns(self::tableColumns())
            ->filters([
                Filters::semester('activity'),
                Filters::academicYear('activity'),
                SelectFilter::make('invitation_status')->label('حالة الدعوة')->options(InvitationStatus::class),
                SelectFilter::make('role')->label('الصفة')->options(GuestRole::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()->modalWidth('3xl'),
                    DeleteAction::make(),
                ]),
            ])
            ->emptyStateHeading('لا يوجد ضيوف مسجلون');
    }

    public static function getPages(): array
    {
        return ['index' => ManageActivityGuests::route('/')];
    }
}
