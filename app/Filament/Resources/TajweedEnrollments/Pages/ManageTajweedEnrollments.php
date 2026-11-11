<?php

namespace App\Filament\Resources\TajweedEnrollments\Pages;

use App\Filament\Resources\TajweedEnrollments\TajweedEnrollmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTajweedEnrollments extends ManageRecords
{
    protected static string $resource = TajweedEnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->modalWidth('3xl'),
        ];
    }
}
