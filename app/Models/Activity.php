<?php

namespace App\Models;

use App\Enums\ActivityStatus;
use App\Enums\ArchiveStatus;
use App\Enums\Team;
use App\Models\Concerns\InSemester;
use App\Models\Concerns\TracksAuthor;
use App\Models\Contracts\TeamOwned;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model implements TeamOwned
{
    use InSemester, TracksAuthor;

    protected $fillable = [
        'semester_id', 'approved_activity_id', 'activity_type_id', 'name', 'held_on', 'location', 'status',
        'expected_attendance', 'actual_attendance', 'rating', 'archive_status', 'drive_folder_url', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'held_on' => 'date',
            'status' => ActivityStatus::class,
            'archive_status' => ArchiveStatus::class,
            'expected_attendance' => 'integer',
            'actual_attendance' => 'integer',
            'rating' => 'integer',
        ];
    }

    public static function owningTeam(): Team
    {
        return Team::Activities;
    }

    public function approvedActivity(): BelongsTo
    {
        return $this->belongsTo(ApprovedActivity::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class, 'activity_type_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(ActivityPurchase::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(ActivityGuest::class);
    }

    public function attendanceRate(): ?float
    {
        if (! $this->expected_attendance || $this->actual_attendance === null) {
            return null;
        }

        return round($this->actual_attendance / $this->expected_attendance * 100, 1);
    }
}
