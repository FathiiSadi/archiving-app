<?php

namespace App\Filament\Resources\QuranCircles\Pages;

use App\Filament\Resources\QuranCircles\QuranCircleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQuranCircles extends ListRecords
{
    protected static string $resource = QuranCircleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
