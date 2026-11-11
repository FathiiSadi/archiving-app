<?php

namespace App\Filament\Resources\CircleEvaluations\Pages;

use App\Filament\Resources\CircleEvaluations\CircleEvaluationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCircleEvaluations extends ManageRecords
{
    protected static string $resource = CircleEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->modalWidth('3xl'),
        ];
    }
}
