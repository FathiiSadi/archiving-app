<?php

namespace App\Models;

use App\Enums\ArchiveStatus;
use App\Enums\Team;
use App\Models\Concerns\InSemester;
use App\Models\Concerns\TracksAuthor;
use App\Models\Contracts\TeamOwned;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TajweedSection extends Model implements TeamOwned
{
    use InSemester, TracksAuthor;

    protected $fillable = [
        'semester_id', 'tajweed_level_id', 'instructor_id', 'supervisor_id', 'name', 'capacity',
        'planned_lessons', 'schedule', 'archive_status', 'drive_folder_url', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'planned_lessons' => 'integer',
            'archive_status' => ArchiveStatus::class,
        ];
    }

    public static function owningTeam(): Team
    {
        return Team::Tajweed;
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(TajweedLevel::class, 'tajweed_level_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(TajweedStaff::class, 'instructor_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(TajweedStaff::class, 'supervisor_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(TajweedLesson::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(TajweedEnrollment::class);
    }
}
