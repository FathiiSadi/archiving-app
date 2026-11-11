<?php

namespace App\Filament\Resources\InternalActivities\Pages;

use App\Filament\Resources\InternalActivities\InternalActivityResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInternalActivity extends CreateRecord
{
    protected static string $resource = InternalActivityResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
