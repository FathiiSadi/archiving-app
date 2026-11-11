<?php

namespace App\Models;

use App\Enums\Team;
use App\Models\Contracts\TeamOwned;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TajweedLevel extends Model implements TeamOwned
{
    protected $fillable = ['name', 'sort_order', 'description'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public static function owningTeam(): Team
    {
        return Team::Tajweed;
    }

    public function sections(): HasMany
    {
        return $this->hasMany(TajweedSection::class);
    }
}
