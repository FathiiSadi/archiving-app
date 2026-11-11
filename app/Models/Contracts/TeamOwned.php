<?php

namespace App\Models\Contracts;

use App\Enums\Team;

/**
 * A record that belongs to one team's archive. Only that team's leader may
 * create or change it (BR-16); management and admins may view it.
 */
interface TeamOwned
{
    public static function owningTeam(): Team;
}
