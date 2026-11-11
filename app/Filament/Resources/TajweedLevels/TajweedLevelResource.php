<?php

namespace App\Filament\Resources\TajweedLevels;

use App\Filament\Resources\TajweedLevels\Pages\ManageTajweedLevels;
use App\Models\TajweedLevel;
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
use Filament\Tables\Table;
use UnitEnum;

class TajweedLevelResource extends Resource
{
    protected static ?string $model = TajweedLevel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3BottomRight;

    protected static string|UnitEnum|null $navigationGroup = 'فريق التجويد';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'مستوى';

    protected static ?string $pluralModelLabel = 'المستويات';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('اسم المستوى')->required()->maxLength(255)->unique(ignoreRecord: true),
            TextInput::make('sort_order')->label('الترتيب')->numeric()->minValue(0)->default(fn () => (TajweedLevel::max('sort_order') ?? 0) + 1),
            Textarea::make('description')->label('الوصف / المنهج')->rows(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('sort_order')->label('#')->alignCenter(),
                TextColumn::make('name')->label('المستوى')->weight('medium'),
                TextColumn::make('description')->label('الوصف')->limit(80)->placeholder('—'),
                TextColumn::make('sections_count')->label('الشعب')->counts('sections')->alignCenter(),
            ])
            ->recordActions([ActionGroup::make([EditAction::make(), DeleteAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageTajweedLevels::route('/')];
    }
}
