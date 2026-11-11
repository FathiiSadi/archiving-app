<?php

namespace App\Models\Contracts;

/**
 * Shared configuration (semesters, budgets, approved activity lists, lookups).
 * Everyone signed in may read it; only admins and management may change it.
 */
interface SettingsRecord
{
}
