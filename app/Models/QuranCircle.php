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

class QuranCircle extends Model implements TeamOwned
{
    use InSemester, TracksAuthor;

    protected $fillable = [
        'semester_id', 'quran_teacher_id', 'name', 'capacity', 'teacher_rating', 'schedule', 'location',
        'archive_status', 'drive_folder_url', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'teacher_rating' => 'decimal:1',
            'archive_status' => ArchiveStatus::class,
        ];
    }

    public static function owningTeam(): Team
    {
        return Team::Quran;
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(QuranTeacher::class, 'quran_teacher_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(RecitationSession::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(CircleEvaluation::class);
    }
}
