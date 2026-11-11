<?php

namespace App\Filament\Resources\TajweedStudents\Pages;

use App\Filament\Resources\TajweedStudents\TajweedStudentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTajweedStudents extends ManageRecords
{
    protected static string $resource = TajweedStudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->modalWidth('3xl'),
        ];
    }
}
