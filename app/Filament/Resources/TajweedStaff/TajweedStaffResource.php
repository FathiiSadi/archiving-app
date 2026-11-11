<?php

namespace App\Filament\Resources\TajweedStaff;

use App\Enums\StaffRole;
use App\Enums\Team;
use App\Filament\Resources\TajweedStaff\Pages\ManageTajweedStaff;
use App\Filament\Support\Columns;
use App\Filament\Support\Fields;
use App\Models\TajweedStaff;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class TajweedStaffResource extends Resource
{
    protected static ?string $model = TajweedStaff::class;

    protected static ?string $slug = 'tajweed-staff';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'فريق التجويد';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'عضو هيئة تدريس';

    protected static ?string $pluralModelLabel = 'المدرّسون والمشرفون';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('الاسم')->required()->maxLength(255),
            ToggleButtons::make('role')->label('الصفة')->options(StaffRole::class)->default(StaffRole::Instructor)->inline()->grouped()->required(),
            Fields::gender(),
            Fields::phone(),
            Toggle::make('is_active')->label('على رأس العمل')->default(true)->inline(false),
            Textarea::make('notes')->label('ملاحظات')->rows(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('الاسم')->searchable()->weight('medium'),
                TextColumn::make('role')->label('الصفة')->badge(),
                TextColumn::make('gender')->label('الجنس')->badge()->toggleable(),
                Columns::phone(Team::Tajweed),
                TextColumn::make('taught_sections_count')->label('شعب يدرّسها')->counts('taughtSections')->alignCenter(),
                TextColumn::make('supervised_sections_count')->label('شعب يشرف عليها')->counts('supervisedSections')->alignCenter()->toggleable(),
                IconColumn::make('is_active')->label('نشط')->boolean()->alignCenter(),
            ])
            ->filters([SelectFilter::make('role')->label('الصفة')->options(StaffRole::class)])
            ->recordActions([ActionGroup::make([EditAction::make()->modalWidth('2xl'), DeleteAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageTajweedStaff::route('/')];
    }
}
