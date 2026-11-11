<?php

namespace App\Filament\Resources\TajweedSections\Pages;

use App\Filament\Resources\TajweedSections\TajweedSectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTajweedSection extends CreateRecord
{
    protected static string $resource = TajweedSectionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
