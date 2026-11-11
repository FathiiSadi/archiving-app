<?php

namespace App\Filament\Resources\QuranCircles\Pages;

use App\Filament\Resources\QuranCircles\QuranCircleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewQuranCircle extends ViewRecord
{
    protected static string $resource = QuranCircleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
