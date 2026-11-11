<?php

namespace App\Filament\Resources\ApprovedActivities\Pages;

use App\Filament\Resources\ApprovedActivities\ApprovedActivityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageApprovedActivities extends ManageRecords
{
    protected static string $resource = ApprovedActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->modalWidth('3xl'),
        ];
    }
}
