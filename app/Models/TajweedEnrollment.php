<?php

namespace App\Models;

use App\Enums\Eligibility;
use App\Enums\Team;
use App\Models\Contracts\TeamOwned;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TajweedEnrollment extends Model implements TeamOwned
{
    protected $fillable = [
        'tajweed_section_id', 'tajweed_student_id', 'recitation_score', 'practical_score',
        'final_exam_score', 'eligibility', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'recitation_score' => 'decimal:2',
            'practical_score' => 'decimal:2',
            'final_exam_score' => 'decimal:2',
            'eligibility' => Eligibility::class,
        ];
    }

    public static function owningTeam(): Team
    {
        return Team::Tajweed;
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(TajweedSection::class, 'tajweed_section_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(TajweedStudent::class, 'tajweed_student_id');
    }
}
