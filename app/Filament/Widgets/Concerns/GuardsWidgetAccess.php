<?php

namespace App\Filament\Widgets\Concerns;

use App\Enums\Team;

/**
 * A widget shows a team's indicators (often financial), so it carries its own access rule
 * instead of relying on the page that embeds it (BR-16). The rule is derived from the
 * widget's namespace: App\Filament\Widgets\{Activities|Quran|Tajweed|InternalAffairs}\* belong
 * to that team; everything else (Overview) is for admins and management only.
 *
 * `canView()` keeps the widget off pages for users without access; the boot hook rejects
 * any direct Livewire request for it.
 */
trait GuardsWidgetAccess
{
    public static function canView(): bool
    {
        $user = auth()->user();

        if ($user === null || ! $user->is_active) {
            return false;
        }

        $team = match (str(static::class)->after('App\\Filament\\Widgets\\')->before('\\')->toString()) {
            'Activities' => Team::Activities,
            'Quran' => Team::Quran,
            'Tajweed' => Team::Tajweed,
            'InternalAffairs' => Team::InternalAffairs,
            default => null,
        };

        return $team !== null ? $user->canViewTeam($team) : ($user->isAdmin() || $user->isManagement());
    }

    public function bootGuardsWidgetAccess(): void
    {
        abort_unless(static::canView(), 403);
    }
}
