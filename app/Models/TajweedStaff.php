<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\StaffRole;
use App\Enums\Team;
use App\Models\Contracts\TeamOwned;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TajweedStaff extends Model implements TeamOwned
{
    protected $table = 'tajweed_staff';

    protected $fillable = ['name', 'gender', 'phone', 'role', 'is_active', 'notes'];

    protected $hidden = ['phone'];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'phone' => 'encrypted',
            'role' => StaffRole::class,
            'is_active' => 'boolean',
        ];
    }

    public static function owningTeam(): Team
    {
        return Team::Tajweed;
    }

    public function taughtSections(): HasMany
    {
        return $this->hasMany(TajweedSection::class, 'instructor_id');
    }

    public function supervisedSections(): HasMany
    {
        return $this->hasMany(TajweedSection::class, 'supervisor_id');
    }
}
