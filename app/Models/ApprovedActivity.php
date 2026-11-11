<?php

namespace App\Models;

use App\Enums\Team;
use App\Models\Concerns\InSemester;
use App\Models\Contracts\SettingsRecord;
use Illuminate\Database\Eloquent\Model;

class ApprovedActivity extends Model implements SettingsRecord
{
    use InSemester;

    protected $fillable = ['semester_id', 'team', 'name', 'estimated_budget', 'notes'];

    protected function casts(): array
    {
        return [
            'team' => Team::class,
            'estimated_budget' => 'decimal:3',
        ];
    }

    /** @return array<int, string> */
    public static function optionsFor(Team $team, ?int $semesterId): array
    {
        if (! $semesterId) {
            return [];
        }

        return static::query()->where('team', $team)->where('semester_id', $semesterId)
            ->orderBy('name')->pluck('name', 'id')->all();
    }
}
