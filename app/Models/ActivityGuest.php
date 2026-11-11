<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\GuestRole;
use App\Enums\InvitationStatus;
use App\Enums\Team;
use App\Models\Contracts\TeamOwned;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityGuest extends Model implements TeamOwned
{
    protected $fillable = ['activity_id', 'name', 'gender', 'phone', 'role', 'affiliation', 'topic', 'invitation_status', 'notes'];

    protected $hidden = ['phone'];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'phone' => 'encrypted',
            'role' => GuestRole::class,
            'invitation_status' => InvitationStatus::class,
        ];
    }

    public static function owningTeam(): Team
    {
        return Team::Activities;
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
