<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Filament\Widgets\InternalAffairs\VendorWhitelist;
use App\Filament\Widgets\Overview\ArchiveCompletenessStats;
use App\Filament\Widgets\Overview\SemesterComparisonTable;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    private function as(UserRole $role): User
    {
        $user = User::where('role', $role)->firstOrFail();
        $this->flushSession();
        $this->app['auth']->forgetGuards();
        $this->actingAs($user);

        return $user;
    }

    public function test_widgets_refuse_users_outside_their_audience(): void
    {
        // A team leader must not load management-wide or another team's (financial) widgets,
        // even by requesting the component directly.
        $this->as(UserRole::QuranLeader);
        Livewire::test(ArchiveCompletenessStats::class)->assertForbidden();
        Livewire::test(SemesterComparisonTable::class)->assertForbidden();
        Livewire::test(VendorWhitelist::class)->assertForbidden();

        $this->as(UserRole::InternalAffairsLeader);
        Livewire::test(VendorWhitelist::class)->assertOk();
        Livewire::test(ArchiveCompletenessStats::class)->assertForbidden();

        $this->as(UserRole::Management);
        Livewire::test(ArchiveCompletenessStats::class)->assertOk();
        Livewire::test(VendorWhitelist::class)->assertOk();
    }

    public function test_leader_landing_on_management_page_gets_no_management_widgets(): void
    {
        $this->as(UserRole::Management);
        $this->get('/panel')->assertSee('ArchiveCompletenessStats', false);

        $this->as(UserRole::ActivitiesLeader);
        $this->get('/panel')->assertDontSee('ArchiveCompletenessStats', false)->assertDontSee('SemesterComparisonTable', false);
    }

    public function test_responses_carry_security_headers(): void
    {
        foreach (['/', '/panel/login'] as $url) {
            $this->get($url)
                ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        }
    }

    public function test_admin_cannot_demote_or_deactivate_themselves(): void
    {
        $admin = $this->as(UserRole::Admin);

        Livewire::test(ManageUsers::class)
            ->callAction(TestAction::make('edit')->table($admin), ['role' => UserRole::Management->value, 'is_active' => false])
            ->assertHasNoFormErrors();

        $admin->refresh();
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue($admin->is_active);
    }

    public function test_new_passwords_must_meet_the_policy(): void
    {
        $this->as(UserRole::Admin);

        $data = ['name' => 'قائد جديد', 'email' => 'new.leader@dal.test', 'role' => UserRole::QuranLeader->value, 'is_active' => true];

        Livewire::test(ManageUsers::class)
            ->callAction(TestAction::make('create'), [...$data, 'password' => 'short1'])
            ->assertHasFormErrors(['password']);

        Livewire::test(ManageUsers::class)
            ->callAction(TestAction::make('create'), [...$data, 'password' => 'Strong-pass-2026'])
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('Strong-pass-2026', User::where('email', 'new.leader@dal.test')->value('password')));
    }

    public function test_users_change_their_own_password_but_not_their_email_or_role(): void
    {
        $leader = $this->as(UserRole::TajweedLeader);
        $this->get('/panel/profile')->assertOk();

        Livewire::test(EditProfile::class)
            ->assertFormFieldDoesNotExist('email')
            ->assertFormFieldDoesNotExist('role')
            ->fillForm(['password' => 'New-secret-2026', 'passwordConfirmation' => 'New-secret-2026', 'currentPassword' => 'wrong'])
            ->call('save')
            ->assertHasFormErrors(['currentPassword']);

        Livewire::test(EditProfile::class)
            ->fillForm(['password' => 'New-secret-2026', 'passwordConfirmation' => 'New-secret-2026', 'currentPassword' => 'password'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('New-secret-2026', $leader->refresh()->password));
        $this->assertSame(UserRole::TajweedLeader, $leader->role);
    }
}
