<?php

namespace App\Filament\Resources\QuranTeachers\Pages;

use App\Filament\Resources\QuranTeachers\QuranTeacherResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageQuranTeachers extends ManageRecords
{
    protected static string $resource = QuranTeacherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->modalWidth('3xl'),
        ];
    }
}
