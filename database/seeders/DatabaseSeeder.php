<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Production-safe: only creates the first admin account from ADMIN_* env values.
     * Demo data lives in DemoSeeder and is never run automatically.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (blank($email) || blank($password)) {
            $this->command?->warn('ADMIN_EMAIL / ADMIN_PASSWORD are not set — no admin account was created.');

            return;
        }

        User::query()->firstOrCreate(
            ['email' => $email],
            ['name' => env('ADMIN_NAME', 'مدير النظام'), 'password' => $password, 'role' => UserRole::Admin, 'is_active' => true],
        );

        $this->command?->info("Admin account ready: {$email}");
    }
}
