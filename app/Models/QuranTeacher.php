<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\Team;
use App\Models\Contracts\TeamOwned;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuranTeacher extends Model implements TeamOwned
{
    protected $fillable = ['name', 'gender', 'phone', 'is_active', 'notes'];

    protected $hidden = ['phone'];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'phone' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    public static function owningTeam(): Team
    {
        return Team::Quran;
    }

    public function circles(): HasMany
    {
        return $this->hasMany(QuranCircle::class);
    }
}
