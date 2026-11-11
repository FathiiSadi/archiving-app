<?php

namespace App\Filament\Resources\RecitationSessions\Pages;

use App\Filament\Resources\RecitationSessions\RecitationSessionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRecitationSessions extends ManageRecords
{
    protected static string $resource = RecitationSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->modalWidth('3xl'),
        ];
    }
}
