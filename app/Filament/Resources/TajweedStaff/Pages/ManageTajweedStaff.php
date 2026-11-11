<?php

namespace App\Filament\Resources\TajweedStaff\Pages;

use App\Filament\Resources\TajweedStaff\TajweedStaffResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTajweedStaff extends ManageRecords
{
    protected static string $resource = TajweedStaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->modalWidth('3xl'),
        ];
    }
}
