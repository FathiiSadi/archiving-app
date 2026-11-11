<?php

namespace App\Filament\Resources\InternalActivities\Pages;

use App\Filament\Resources\InternalActivities\InternalActivityResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewInternalActivity extends ViewRecord
{
    protected static string $resource = InternalActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
