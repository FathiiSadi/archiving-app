<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\Team;
use App\Models\Contracts\TeamOwned;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TajweedStudent extends Model implements TeamOwned
{
    protected $fillable = ['name', 'gender', 'phone', 'student_number', 'notes'];

    protected $hidden = ['phone'];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'phone' => 'encrypted',
        ];
    }

    public static function owningTeam(): Team
    {
        return Team::Tajweed;
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(TajweedEnrollment::class);
    }
}
