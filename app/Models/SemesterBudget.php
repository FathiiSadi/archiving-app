<?php

namespace App\Models;

use App\Enums\Team;
use App\Models\Contracts\SettingsRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SemesterBudget extends Model implements SettingsRecord
{
    protected $fillable = ['semester_id', 'team', 'approved_budget', 'budget_cap'];

    protected function casts(): array
    {
        return [
            'team' => Team::class,
            'approved_budget' => 'decimal:3',
            'budget_cap' => 'decimal:3',
        ];
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }
}
