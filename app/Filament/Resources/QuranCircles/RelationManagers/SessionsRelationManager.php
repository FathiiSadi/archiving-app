<?php

namespace App\Filament\Resources\QuranCircles\RelationManagers;

use App\Filament\Resources\RecitationSessions\RecitationSessionResource;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class SessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sessions';

    protected static ?string $title = 'جلسات التسميع';

    protected static ?string $modelLabel = 'جلسة';

    protected static ?string $pluralModelLabel = 'الجلسات';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components(RecitationSessionResource::formComponents(withCircle: false));
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('held_on', 'desc')
            ->columns(RecitationSessionResource::tableColumns(withCircle: false))
            ->headerActions([CreateAction::make()->modalWidth('3xl')])
            ->recordActions([EditAction::make()->modalWidth('3xl'), DeleteAction::make()])
            ->emptyStateHeading('لا توجد جلسات لهذه الحلقة');
    }
}
