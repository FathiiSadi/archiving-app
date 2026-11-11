<?php

namespace App\Models;

use App\Enums\Team;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isManagement(): bool
    {
        return $this->role === UserRole::Management;
    }

    public function team(): ?Team
    {
        return $this->role?->team();
    }

    /** Read access to a team's archive and dashboard. */
    public function canViewTeam(Team $team): bool
    {
        return $this->isAdmin() || $this->isManagement() || $this->team() === $team;
    }

    /** Data entry is restricted to the team's own leader (BR-16). */
    public function canManageTeam(Team $team): bool
    {
        return $this->team() === $team;
    }

    /** Phone numbers are shown in full only to the owning team's leader and admins (BR-16). */
    public function canSeeContactsOf(Team $team): bool
    {
        return $this->isAdmin() || $this->team() === $team;
    }

    public function canManageSettings(): bool
    {
        return $this->isAdmin() || $this->isManagement();
    }
}
