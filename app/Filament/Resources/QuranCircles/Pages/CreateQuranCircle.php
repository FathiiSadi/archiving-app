<?php

namespace App\Filament\Resources\QuranCircles\Pages;

use App\Filament\Resources\QuranCircles\QuranCircleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuranCircle extends CreateRecord
{
    protected static string $resource = QuranCircleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
