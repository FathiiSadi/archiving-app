<?php

namespace App\Filament\Widgets\InternalAffairs;

class VendorWhitelist extends VendorListTable
{
    protected function suitable(): bool
    {
        return true;
    }
}
