<?php

namespace App\Filament\Resources\ActivityGuests\Pages;

use App\Filament\Resources\ActivityGuests\ActivityGuestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageActivityGuests extends ManageRecords
{
    protected static string $resource = ActivityGuestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->modalWidth('3xl'),
        ];
    }
}
