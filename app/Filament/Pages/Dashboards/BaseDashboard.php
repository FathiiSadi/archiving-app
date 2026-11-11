<?php

namespace App\Filament\Pages\Dashboards;

use App\Enums\Term;
use App\Models\Semester;
use App\Support\SemesterScope;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

/** Shared year / semester filter (BR-15) for every dashboard. */
abstract class BaseDashboard extends Dashboard
{
    use HasFiltersForm;

    protected static string|UnitEnum|null $navigationGroup = 'لوحات المتابعة';

    public function filtersForm(Schema $schema): Schema
    {
        $defaults = SemesterScope::defaults();

        return $schema->components([
            Section::make()
                ->compact()
                ->columnSpanFull()
                ->columns(['default' => 1, 'sm' => 2, 'lg' => 4])
                ->schema([
                    Select::make('academic_year')
                        ->label('العام الجامعي')
                        ->options(fn () => Semester::yearOptions())
                        ->placeholder('كل الأعوام')
                        ->default($defaults['academic_year'])
                        ->native(false),
                    Select::make('term')
                        ->label('الفصل')
                        ->options(Term::class)
                        ->placeholder('كل الفصول')
                        ->default($defaults['term'])
                        ->native(false),
                ]),
        ])->columns(1);
    }

    public function getColumns(): int|array
    {
        return ['default' => 1, 'lg' => 2];
    }
}
