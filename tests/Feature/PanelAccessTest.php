<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Activity;
use App\Models\InternalActivity;
use App\Models\QuranCircle;
use App\Models\TajweedSection;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PanelAccessTest extends TestCase
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

    /** Every list / dashboard page in the panel, grouped by the team that owns it. */
    public static function pages(): array
    {
        return [
            'activities' => ['/panel/activities-dashboard', '/panel/activities', '/panel/activity-purchases', '/panel/activity-guests'],
            'quran' => ['/panel/quran-dashboard', '/panel/quran-teachers', '/panel/quran-circles', '/panel/recitation-sessions', '/panel/circle-evaluations'],
            'tajweed' => ['/panel/tajweed-dashboard', '/panel/tajweed-staff', '/panel/tajweed-levels', '/panel/tajweed-sections', '/panel/tajweed-lessons', '/panel/tajweed-students', '/panel/tajweed-enrollments'],
            'internal_affairs' => ['/panel/internal-affairs-dashboard', '/panel/internal-activities', '/panel/vendors'],
        ];
    }

    public function test_home_page_renders_without_personal_or_financial_data(): void
    {
        $response = $this->get('/')->assertOk();
        $response->assertSee('أرشيف دال');
        $response->assertDontSee('د.أ');
        $response->assertDontSee('079');
    }

    public function test_admin_and_management_see_every_page_and_dashboard(): void
    {
        foreach ([UserRole::Admin, UserRole::Management] as $role) {
            $this->as($role);
            $this->get('/panel')->assertOk()->assertSee('لوحة الإدارة');

            foreach (self::pages() as $urls) {
                foreach ($urls as $url) {
                    $this->get($url)->assertOk();
                }
            }

            foreach (['/panel/semesters', '/panel/approved-activities', '/panel/activity-types'] as $url) {
                $this->get($url)->assertOk();
            }
        }
    }

    #[DataProvider('leaders')]
    public function test_leaders_only_reach_their_own_team(UserRole $role, string $team): void
    {
        $this->as($role);

        $this->get('/panel')->assertRedirect();

        foreach (self::pages() as $owner => $urls) {
            foreach ($urls as $url) {
                $response = $this->get($url);
                if ($response->status() === 500) {
                    $this->fail($url.': '.substr((string) $response->exception, 0, 1500));
                }
                $response->assertStatus($owner === $team ? 200 : 403);
            }
        }

        $this->get('/panel/users')->assertForbidden();
    }

    public static function leaders(): array
    {
        return [
            'activities' => [UserRole::ActivitiesLeader, 'activities'],
            'quran' => [UserRole::QuranLeader, 'quran'],
            'tajweed' => [UserRole::TajweedLeader, 'tajweed'],
            'internal affairs' => [UserRole::InternalAffairsLeader, 'internal_affairs'],
        ];
    }

    public function test_record_pages_render_for_owner_and_are_read_only_for_management(): void
    {
        $records = [
            [UserRole::ActivitiesLeader, 'activities', Activity::first()],
            [UserRole::QuranLeader, 'quran-circles', QuranCircle::first()],
            [UserRole::TajweedLeader, 'tajweed-sections', TajweedSection::first()],
            [UserRole::InternalAffairsLeader, 'internal-activities', InternalActivity::first()],
        ];

        foreach ($records as [$leader, $slug, $record]) {
            $this->as($leader);
            $this->get("/panel/{$slug}/create")->assertOk();
            $this->get("/panel/{$slug}/{$record->id}/edit")->assertOk();
            $this->get("/panel/{$slug}/{$record->id}")->assertOk();

            $this->as(UserRole::Management);
            $this->get("/panel/{$slug}/{$record->id}")->assertOk();
            $this->get("/panel/{$slug}/{$record->id}/edit")->assertForbidden();
            $this->get("/panel/{$slug}/create")->assertForbidden();
        }
    }

    public function test_admin_cannot_enter_team_data_but_manages_users(): void
    {
        $admin = $this->as(UserRole::Admin);

        $this->assertFalse($admin->can('create', Activity::class));
        $this->assertTrue($admin->can('viewAny', Activity::class));
        $this->get('/panel/users')->assertOk();
    }

    public function test_inactive_users_are_denied(): void
    {
        $user = User::where('role', UserRole::ActivitiesLeader)->first();
        $user->update(['is_active' => false]);

        $this->actingAs($user)->get('/panel/activities')->assertForbidden();
    }
}
