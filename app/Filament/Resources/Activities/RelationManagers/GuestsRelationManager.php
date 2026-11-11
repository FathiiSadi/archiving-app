<?php

namespace App\Filament\Resources\Activities\RelationManagers;

use App\Filament\Resources\ActivityGuests\ActivityGuestResource;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class GuestsRelationManager extends RelationManager
{
    protected static string $relationship = 'guests';

    protected static ?string $title = 'الضيوف والمتحدثون';

    protected static ?string $modelLabel = 'ضيف';

    protected static ?string $pluralModelLabel = 'الضيوف';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components(ActivityGuestResource::formComponents(withActivity: false));
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns(ActivityGuestResource::tableColumns(withActivity: false))
            ->headerActions([CreateAction::make()->modalWidth('3xl')])
            ->recordActions([
                EditAction::make()->modalWidth('3xl'),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('لا يوجد ضيوف لهذا النشاط');
    }
}
