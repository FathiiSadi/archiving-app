<?php

namespace App\Filament\Resources\TajweedLessons\Pages;

use App\Filament\Resources\TajweedLessons\TajweedLessonResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTajweedLessons extends ManageRecords
{
    protected static string $resource = TajweedLessonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->modalWidth('3xl'),
        ];
    }
}
