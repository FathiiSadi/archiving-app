<?php

namespace App\Filament\Resources\TajweedLevels\Pages;

use App\Filament\Resources\TajweedLevels\TajweedLevelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTajweedLevels extends ManageRecords
{
    protected static string $resource = TajweedLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->modalWidth('3xl'),
        ];
    }
}
