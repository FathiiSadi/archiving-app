<?php

namespace App\Models;

use App\Enums\Team;
use App\Models\Contracts\TeamOwned;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityPurchase extends Model implements TeamOwned
{
    protected $fillable = [
        'activity_id', 'purchased_on', 'supplier', 'description', 'quantity', 'total_cost',
        'within_budget', 'supplier_rating', 'invoice_url', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchased_on' => 'date',
            'quantity' => 'decimal:2',
            'total_cost' => 'decimal:3',
            'within_budget' => 'boolean',
            'supplier_rating' => 'integer',
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
