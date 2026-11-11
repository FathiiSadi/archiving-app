<?php

namespace App\Filament\Resources\TajweedSections\RelationManagers;

use App\Filament\Resources\TajweedEnrollments\TajweedEnrollmentResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    protected static ?string $title = 'الطلاب والنتائج';

    protected static ?string $modelLabel = 'طالب';

    protected static ?string $pluralModelLabel = 'الطلاب';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components(TajweedEnrollmentResource::formComponents(withSection: false));
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('student'))
            ->columns(TajweedEnrollmentResource::tableColumns(withSection: false))
            ->headerActions([CreateAction::make()->label('تسجيل طالب')->modalWidth('3xl')])
            ->recordActions([EditAction::make()->modalWidth('3xl'), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([TajweedEnrollmentResource::eligibilityBulkAction()])])
            ->emptyStateHeading('لا يوجد طلاب في هذه الشعبة');
    }
}
