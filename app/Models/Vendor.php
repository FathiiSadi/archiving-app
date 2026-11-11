<?php

namespace App\Models;

use App\Enums\Team;
use App\Enums\VendorType;
use App\Models\Contracts\TeamOwned;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model implements TeamOwned
{
    protected $fillable = [
        'name', 'type', 'service', 'contact_person', 'phone', 'rating', 'invoice_url', 'suitable_for_future', 'notes',
    ];

    protected $hidden = ['phone'];

    protected function casts(): array
    {
        return [
            'type' => VendorType::class,
            'phone' => 'encrypted',
            'rating' => 'decimal:1',
            'suitable_for_future' => 'boolean',
        ];
    }

    public static function owningTeam(): Team
    {
        return Team::InternalAffairs;
    }

    public function internalActivities(): HasMany
    {
        return $this->hasMany(InternalActivity::class);
    }
}
