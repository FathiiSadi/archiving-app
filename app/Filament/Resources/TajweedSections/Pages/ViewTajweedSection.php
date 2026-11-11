<?php

namespace App\Filament\Resources\TajweedSections\Pages;

use App\Filament\Resources\TajweedSections\TajweedSectionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTajweedSection extends ViewRecord
{
    protected static string $resource = TajweedSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
