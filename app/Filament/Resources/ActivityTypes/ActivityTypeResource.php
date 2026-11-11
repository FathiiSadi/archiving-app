<?php

namespace App\Filament\Resources\ActivityTypes;

use App\Filament\Resources\ActivityTypes\Pages\ManageActivityTypes;
use App\Models\ActivityType;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class ActivityTypeResource extends Resource
{
    protected static ?string $model = ActivityType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'الإعدادات';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'نوع نشاط';

    protected static ?string $pluralModelLabel = 'أنواع الأنشطة';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('اسم النوع')->required()->maxLength(255)->unique(ignoreRecord: true),
            Toggle::make('is_active')->label('مفعّل')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('النوع')->weight('medium'),
                ToggleColumn::make('is_active')->label('مفعّل')
                    ->disabled(fn () => ! auth()->user()?->canManageSettings()),
            ])
            ->recordActions([EditAction::make()->modalWidth('lg'), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageActivityTypes::route('/')];
    }
}
