<?php

namespace App\Filament\Widgets\Activities;

use App\Enums\InvitationStatus;
use App\Filament\Widgets\DalChartWidget;
use App\Support\Kpi\ActivitiesKpis;

class GuestsStatusChart extends DalChartWidget
{
    protected ?string $heading = 'الضيوف والمتحدثون حسب حالة الدعوة';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $counts = (new ActivitiesKpis($this->semesterIds()))->guestsByStatus();
        $statuses = collect(InvitationStatus::cases());
        $palette = [
            InvitationStatus::Invited->value => 'stone',
            InvitationStatus::Approved->value => 'slate',
            InvitationStatus::RejectedByDeanship->value => 'clay',
            InvitationStatus::Apologized->value => 'brass',
            InvitationStatus::Attended->value => 'ink',
            InvitationStatus::NotAttended->value => 'plum',
        ];

        return [
            'datasets' => [[
                'label' => 'العدد',
                'data' => $statuses->map(fn ($s) => $counts[$s->value] ?? 0)->all(),
                'backgroundColor' => $statuses->map(fn ($s) => $this->color($palette[$s->value]))->all(),
                'borderRadius' => 4,
                'maxBarThickness' => 18,
            ]],
            'labels' => $statuses->map->getLabel()->all(),
        ];
    }

    protected function getOptions(): array
    {
        $options = $this->baseOptions();
        $options['indexAxis'] = 'y';
        $options['plugins']['legend']['display'] = false;
        $options['scales']['x']['reverse'] = true;
        $options['scales']['x']['grid'] = ['color' => 'rgba(120, 113, 108, 0.12)'];
        $options['scales']['y']['grid'] = ['display' => false];
        $options['scales']['x']['ticks']['precision'] = 0;

        return $options;
    }
}
