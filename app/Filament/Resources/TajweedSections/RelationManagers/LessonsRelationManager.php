<?php

namespace App\Filament\Resources\TajweedSections\RelationManagers;

use App\Filament\Resources\TajweedLessons\TajweedLessonResource;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class LessonsRelationManager extends RelationManager
{
    protected static string $relationship = 'lessons';

    protected static ?string $title = 'المحاضرات والدروس';

    protected static ?string $modelLabel = 'درس';

    protected static ?string $pluralModelLabel = 'الدروس';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components(TajweedLessonResource::formComponents(withSection: false));
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('held_on', 'desc')
            ->columns(TajweedLessonResource::tableColumns(withSection: false))
            ->headerActions([CreateAction::make()->modalWidth('3xl')])
            ->recordActions([EditAction::make()->modalWidth('3xl'), DeleteAction::make()])
            ->emptyStateHeading('لا توجد دروس لهذه الشعبة');
    }
}
