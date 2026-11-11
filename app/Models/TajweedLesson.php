<?php

namespace App\Models;

use App\Enums\LessonType;
use App\Enums\Team;
use App\Models\Contracts\TeamOwned;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TajweedLesson extends Model implements TeamOwned
{
    protected $fillable = [
        'tajweed_section_id', 'held_on', 'type', 'topic', 'enrolled_count', 'present_count',
        'interaction_rating', 'correction_rating', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'held_on' => 'date',
            'type' => LessonType::class,
            'enrolled_count' => 'integer',
            'present_count' => 'integer',
            'interaction_rating' => 'integer',
            'correction_rating' => 'integer',
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

    public function attendanceRate(): ?float
    {
        return $this->enrolled_count ? round($this->present_count / $this->enrolled_count * 100, 1) : null;
    }
}
