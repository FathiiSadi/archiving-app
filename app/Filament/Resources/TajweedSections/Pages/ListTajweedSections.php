<?php

namespace App\Filament\Resources\TajweedSections\Pages;

use App\Filament\Resources\TajweedSections\TajweedSectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTajweedSections extends ListRecords
{
    protected static string $resource = TajweedSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
