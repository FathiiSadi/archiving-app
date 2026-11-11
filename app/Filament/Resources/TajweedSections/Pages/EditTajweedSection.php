<?php

namespace App\Filament\Resources\TajweedSections\Pages;

use App\Filament\Resources\TajweedSections\TajweedSectionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTajweedSection extends EditRecord
{
    protected static string $resource = TajweedSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
