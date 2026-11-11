<?php

namespace App\Providers;

use App\Http\Responses\LoginResponse;
use App\Models\Contracts\SettingsRecord;
use App\Models\Contracts\TeamOwned;
use App\Models\User;
use App\Support\Kpi\KpiCache;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\Filament\Auth\Http\Responses\Contracts\LoginResponse::class, LoginResponse::class);
    }

    public function boot(): void
    {
        Date::use(CarbonImmutable::class);
        Model::shouldBeStrict(! $this->app->isProduction());

        // One password policy for the users screen and the profile page.
        Password::defaults(fn () => Password::min(10)->letters()->numbers());

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        $this->registerAuthorization();
        $this->flushIndicatorsOnChange();
    }

    /** Any change to archive data or settings invalidates the cached indicators. */
    protected function flushIndicatorsOnChange(): void
    {
        foreach (['saved', 'deleted'] as $event) {
            Event::listen("eloquent.{$event}: *", function (string $name, array $payload): void {
                $model = $payload[0] ?? null;

                if ($model instanceof TeamOwned || $model instanceof SettingsRecord) {
                    KpiCache::flush();
                }
            });
        }
    }

    /**
     * One rule set for every record in the system (BR-16):
     *  - team records: everyone with read access to the team may view; only the team's leader may write;
     *  - settings: everyone signed in may view; admins and management may write;
     *  - users: admins only.
     */
    protected function registerAuthorization(): void
    {
        Gate::before(function (User $user, string $ability, array $arguments): ?bool {
            if (! $user->is_active) {
                return false;
            }

            $target = $arguments[0] ?? null;
            $class = is_object($target) ? $target::class : $target;

            if (! is_string($class) || ! is_subclass_of($class, Model::class)) {
                return null;
            }

            $isRead = in_array($ability, ['viewAny', 'view'], true);

            if (is_subclass_of($class, TeamOwned::class)) {
                $team = $class::owningTeam();

                return $isRead ? $user->canViewTeam($team) : $user->canManageTeam($team);
            }

            if (is_subclass_of($class, SettingsRecord::class)) {
                return $isRead ? true : $user->canManageSettings();
            }

            if ($class === User::class) {
                return $user->isAdmin();
            }

            return null;
        });
    }
}
