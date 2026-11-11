<?php

namespace App\Models;

use App\Enums\ArchiveStatus;
use App\Enums\Team;
use App\Models\Concerns\InSemester;
use App\Models\Concerns\TracksAuthor;
use App\Models\Contracts\TeamOwned;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalActivity extends Model implements TeamOwned
{
    use InSemester, TracksAuthor;

    protected $fillable = [
        'semester_id', 'approved_activity_id', 'vendor_id', 'held_on', 'cost_per_person', 'participants',
        'revenue', 'actual_cost', 'final_rating', 'invoice_url', 'archive_status', 'drive_folder_url', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'held_on' => 'date',
            'cost_per_person' => 'decimal:3',
            'participants' => 'integer',
            'revenue' => 'decimal:3',
            'actual_cost' => 'decimal:3',
            'net_result' => 'decimal:3',
            'final_rating' => 'integer',
            'archive_status' => ArchiveStatus::class,
        ];
    }

    protected static function booted(): void
    {
        // BR-12: surplus / deficit is always derived, never typed in.
        static::saving(function (InternalActivity $activity): void {
            $activity->net_result = round((float) $activity->revenue - (float) $activity->actual_cost, 3);
        });
    }

    public static function owningTeam(): Team
    {
        return Team::InternalAffairs;
    }

    public function approvedActivity(): BelongsTo
    {
        return $this->belongsTo(ApprovedActivity::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
