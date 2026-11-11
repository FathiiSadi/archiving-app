<?php

namespace App\Filament\Resources\TajweedStudents;

use App\Enums\Team;
use App\Filament\Resources\TajweedStudents\Pages\ManageTajweedStudents;
use App\Filament\Support\Columns;
use App\Filament\Support\Fields;
use App\Models\TajweedStudent;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class TajweedStudentResource extends Resource
{
    protected static ?string $model = TajweedStudent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'فريق التجويد';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'طالب';

    protected static ?string $pluralModelLabel = 'الطلاب';

    protected static ?string $recordTitleAttribute = 'name';

    public static function formComponents(): array
    {
        return [
            TextInput::make('name')->label('اسم الطالب')->required()->maxLength(255),
            TextInput::make('student_number')->label('الرقم الجامعي')->maxLength(32)->extraInputAttributes(['dir' => 'ltr']),
            Fields::gender(),
            Fields::phone(),
            Textarea::make('notes')->label('ملاحظات')->rows(2)->columnSpanFull(),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components(self::formComponents());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('الطالب')->searchable()->weight('medium'),
                TextColumn::make('student_number')->label('الرقم الجامعي')->searchable()->placeholder('—'),
                TextColumn::make('gender')->label('الجنس')->badge(),
                Columns::phone(Team::Tajweed),
                TextColumn::make('enrollments_count')->label('الشعب المسجل بها')->counts('enrollments')->alignCenter(),
            ])
            ->filters([SelectFilter::make('gender')->label('الجنس')->options(\App\Enums\Gender::class)])
            ->recordActions([ActionGroup::make([EditAction::make()->modalWidth('2xl'), DeleteAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageTajweedStudents::route('/')];
    }
}
