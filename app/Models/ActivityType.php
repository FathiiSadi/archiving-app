<?php

namespace App\Models;

use App\Models\Contracts\SettingsRecord;
use Illuminate\Database\Eloquent\Model;

class ActivityType extends Model implements SettingsRecord
{
    protected $fillable = ['name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
