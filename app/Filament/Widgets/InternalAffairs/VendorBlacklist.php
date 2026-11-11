<?php

namespace App\Filament\Widgets\InternalAffairs;

class VendorBlacklist extends VendorListTable
{
    protected function suitable(): bool
    {
        return false;
    }
}
