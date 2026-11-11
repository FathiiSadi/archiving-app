<?php

namespace App\Filament\Resources\QuranTeachers;

use App\Enums\Team;
use App\Filament\Resources\QuranTeachers\Pages\ManageQuranTeachers;
use App\Filament\Support\Columns;
use App\Filament\Support\Fields;
use App\Models\QuranTeacher;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class QuranTeacherResource extends Resource
{
    protected static ?string $model = QuranTeacher::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'فريق التحفيظ';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'معلم';

    protected static ?string $pluralModelLabel = 'سجل المعلمين';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('اسم المعلم')->required()->maxLength(255),
            Fields::phone(),
            Fields::gender(),
            Toggle::make('is_active')->label('على رأس العمل')->default(true)->inline(false),
            Textarea::make('notes')->label('ملاحظات')->rows(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('المعلم')->searchable()->weight('medium'),
                TextColumn::make('gender')->label('الجنس')->badge(),
                Columns::phone(Team::Quran),
                TextColumn::make('circles_count')->label('الحلقات')->counts('circles')->alignCenter(),
                TextColumn::make('circles_avg_teacher_rating')->label('متوسط التقييم')->avg('circles', 'teacher_rating')
                    ->formatStateUsing(fn ($state) => $state === null ? null : \App\Support\Kpi\Kpi::ratio(number_format((float) $state, 1), 10))
                    ->badge()->color('primary')->placeholder('—'),
                IconColumn::make('is_active')->label('نشط')->boolean()->alignCenter(),
            ])
            ->filters([TernaryFilter::make('is_active')->label('على رأس العمل')])
            ->recordActions([ActionGroup::make([EditAction::make()->modalWidth('2xl'), DeleteAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageQuranTeachers::route('/')];
    }
}
