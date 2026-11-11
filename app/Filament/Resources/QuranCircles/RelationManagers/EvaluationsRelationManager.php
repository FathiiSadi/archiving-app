<?php

namespace App\Filament\Resources\QuranCircles\RelationManagers;

use App\Filament\Resources\CircleEvaluations\CircleEvaluationResource;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class EvaluationsRelationManager extends RelationManager
{
    protected static string $relationship = 'evaluations';

    protected static ?string $title = 'تقييم الحلقة';

    protected static ?string $modelLabel = 'تقييم';

    protected static ?string $pluralModelLabel = 'التقييمات';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components(CircleEvaluationResource::formComponents(withCircle: false));
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('evaluated_on', 'desc')
            ->columns(CircleEvaluationResource::tableColumns(withCircle: false))
            ->headerActions([CreateAction::make()->modalWidth('3xl')])
            ->recordActions([EditAction::make()->modalWidth('3xl'), DeleteAction::make()])
            ->emptyStateHeading('لا توجد تقييمات لهذه الحلقة');
    }
}
