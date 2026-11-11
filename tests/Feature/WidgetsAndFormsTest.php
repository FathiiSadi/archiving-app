<?php

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\ArchiveStatus;
use App\Enums\Team;
use App\Enums\UserRole;
use App\Filament\Resources\Activities\Pages\CreateActivity;
use App\Filament\Resources\Activities\Pages\EditActivity;
use App\Filament\Resources\Activities\RelationManagers\PurchasesRelationManager;
use App\Filament\Resources\InternalActivities\Pages\CreateInternalActivity;
use App\Filament\Resources\RecitationSessions\Pages\ManageRecitationSessions;
use App\Models\Activity;
use App\Models\ApprovedActivity;
use App\Models\InternalActivity;
use App\Models\QuranCircle;
use App\Models\RecitationSession;
use App\Models\Semester;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WidgetsAndFormsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function as(UserRole $role): User
    {
        $user = User::where('role', $role)->firstOrFail();
        $this->actingAs($user);

        return $user;
    }

    public function test_every_dashboard_widget_renders_for_each_filter_scope(): void
    {
        $this->as(UserRole::Management);

        $widgets = collect(glob(app_path('Filament/Widgets/*/*.php')))
            ->map(fn ($f) => 'App\\Filament\\Widgets\\'.str_replace(['/', '.php'], ['\\', ''], str($f)->after('Widgets/')))
            ->reject(fn ($c) => (new \ReflectionClass($c))->isAbstract() || (new \ReflectionClass($c))->isTrait());

        $this->assertGreaterThan(15, $widgets->count());

        $scopes = [
            ['academic_year' => '2025/2026', 'term' => 'second'],
            ['academic_year' => '2025/2026', 'term' => null],
            ['academic_year' => null, 'term' => null],
            ['academic_year' => '2030/2031', 'term' => 'first'], // empty scope
        ];

        foreach ($widgets as $widget) {
            foreach ($scopes as $filters) {
                Livewire::test($widget, ['pageFilters' => $filters])->assertOk();
            }
        }
    }

    public function test_activities_leader_archives_an_activity_with_a_purchase(): void
    {
        $this->as(UserRole::ActivitiesLeader);
        $semester = Semester::current();
        $approved = ApprovedActivity::where('team', Team::Activities)->where('semester_id', $semester->id)->first();

        Livewire::test(CreateActivity::class)
            ->fillForm([
                'semester_id' => $semester->id,
                'approved_activity_id' => $approved->id,
                'status' => ActivityStatus::Completed,
                'held_on' => '2026-03-01',
                'expected_attendance' => 80,
                'actual_attendance' => 64,
                'rating' => 4,
                'archive_status' => ArchiveStatus::Complete,
                'drive_folder_url' => 'https://drive.google.com/drive/folders/1TestFolderAbcdef',
            ])
            ->assertSchemaStateSet(['name' => $approved->name])
            ->call('create')
            ->assertHasNoFormErrors();

        $activity = Activity::latest('id')->first();
        $this->assertSame($approved->name, $activity->name);
        $this->assertEquals(80.0, $activity->attendanceRate());
        $this->assertNotNull($activity->created_by);

        Livewire::test(PurchasesRelationManager::class, ['ownerRecord' => $activity, 'pageClass' => EditActivity::class])
            ->callAction(TestAction::make('create')->table(), [
                'supplier' => 'مطبعة الاختبار',
                'description' => 'شهادات',
                'quantity' => 30,
                'total_cost' => 45.5,
                'within_budget' => true,
                'supplier_rating' => 5,
                'invoice_url' => 'https://drive.google.com/file/d/1InvoiceTestIdXy/view',
            ])
            ->assertHasNoActionErrors();

        $this->assertEquals(45.5, (float) $activity->purchases()->sum('total_cost'));
    }

    public function test_completed_activity_requires_actual_attendance_and_rating(): void
    {
        $this->as(UserRole::ActivitiesLeader);

        Livewire::test(CreateActivity::class)
            ->fillForm([
                'semester_id' => Semester::current()->id,
                'name' => 'نشاط',
                'status' => ActivityStatus::Completed,
                'expected_attendance' => 10,
                'actual_attendance' => null,
                'rating' => null,
                'drive_folder_url' => 'http://not-secure.example.com',
            ])
            ->call('create')
            ->assertHasFormErrors(['actual_attendance' => 'required', 'rating' => 'required', 'drive_folder_url']);
    }

    public function test_quran_leader_logs_a_session_quickly(): void
    {
        $this->as(UserRole::QuranLeader);
        $circle = QuranCircle::where('semester_id', Semester::current()->id)->first();

        Livewire::test(ManageRecitationSessions::class)
            ->callAction(CreateAction::class, [
                'quran_circle_id' => $circle->id,
                'held_on' => '2026-04-01',
                'teacher_attendance' => 'held',
                'mode' => 'in_person',
                'duration_minutes' => 60,
                'total_students' => 12,
                'present_students' => 10,
                'new_pages' => 5,
                'revision_pages' => 7.5,
            ])
            ->assertHasNoActionErrors();

        $this->assertEquals(12.5, (float) RecitationSession::latest('id')->first()->pages_recited);
    }

    public function test_internal_affairs_activity_must_come_from_approved_list(): void
    {
        $this->as(UserRole::InternalAffairsLeader);
        $semester = Semester::current();
        $approved = ApprovedActivity::where('team', Team::InternalAffairs)->where('semester_id', $semester->id)->first();

        Livewire::test(CreateInternalActivity::class)
            ->fillForm(['semester_id' => $semester->id, 'approved_activity_id' => null])
            ->call('create')
            ->assertHasFormErrors(['approved_activity_id' => 'required']);

        Livewire::test(CreateInternalActivity::class)
            ->fillForm([
                'semester_id' => $semester->id,
                'approved_activity_id' => $approved->id,
                'held_on' => '2026-04-10',
                'cost_per_person' => 4,
                'participants' => 25,
                'actual_cost' => 90,
                'archive_status' => ArchiveStatus::Incomplete,
            ])
            ->assertSchemaStateSet(['revenue' => 100])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertEquals(10.0, (float) InternalActivity::latest('id')->first()->net_result);
    }
}
