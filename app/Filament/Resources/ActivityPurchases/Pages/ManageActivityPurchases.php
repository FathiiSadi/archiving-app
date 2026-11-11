<?php

namespace App\Filament\Resources\ActivityPurchases\Pages;

use App\Filament\Resources\ActivityPurchases\ActivityPurchaseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageActivityPurchases extends ManageRecords
{
    protected static string $resource = ActivityPurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->modalWidth('3xl'),
        ];
    }
}
