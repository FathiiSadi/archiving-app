<?php

namespace App\Models;

use App\Enums\SessionMode;
use App\Enums\TeacherAttendance;
use App\Enums\Team;
use App\Models\Contracts\TeamOwned;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecitationSession extends Model implements TeamOwned
{
    protected $fillable = [
        'quran_circle_id', 'held_on', 'duration_minutes', 'mode', 'teacher_attendance', 'is_compensation',
        'total_students', 'present_students', 'new_pages', 'revision_pages', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'held_on' => 'date',
            'mode' => SessionMode::class,
            'teacher_attendance' => TeacherAttendance::class,
            'is_compensation' => 'boolean',
            'duration_minutes' => 'integer',
            'total_students' => 'integer',
            'present_students' => 'integer',
            'new_pages' => 'decimal:2',
            'revision_pages' => 'decimal:2',
            'pages_recited' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (RecitationSession $session): void {
            // A session the teacher apologised for did not take place.
            if ($session->teacher_attendance === TeacherAttendance::Apologized) {
                $session->present_students = 0;
                $session->new_pages = 0;
                $session->revision_pages = 0;
                $session->duration_minutes = 0;
            }

            // BR-06: pages recited = new memorisation + revision.
            $session->pages_recited = (float) $session->new_pages + (float) $session->revision_pages;
        });
    }

    public static function owningTeam(): Team
    {
        return Team::Quran;
    }

    public function circle(): BelongsTo
    {
        return $this->belongsTo(QuranCircle::class, 'quran_circle_id');
    }
}
