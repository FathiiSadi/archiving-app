<?php

namespace Tests\Feature;

use App\Enums\TeacherAttendance;
use App\Enums\Term;
use App\Models\ActivityGuest;
use App\Models\ApprovedActivity;
use App\Models\CircleEvaluation;
use App\Models\InternalActivity;
use App\Models\QuranCircle;
use App\Models\QuranTeacher;
use App\Models\RecitationSession;
use App\Models\Semester;
use App\Support\DriveLink;
use App\Support\Kpi\QuranKpis;
use App\Support\PhoneMask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CalculationsTest extends TestCase
{
    use RefreshDatabase;

    private function circle(): QuranCircle
    {
        $semester = Semester::create(['academic_year' => '2025/2026', 'term' => Term::First]);

        return QuranCircle::create([
            'semester_id' => $semester->id,
            'quran_teacher_id' => QuranTeacher::create(['name' => 'معلم'])->id,
            'name' => 'حلقة',
            'capacity' => 10,
        ]);
    }

    public function test_pages_recited_is_new_plus_revision(): void
    {
        $session = RecitationSession::create([
            'quran_circle_id' => $this->circle()->id, 'held_on' => '2025-10-01',
            'total_students' => 10, 'present_students' => 8, 'new_pages' => 4.5, 'revision_pages' => 10,
        ]);

        $this->assertEquals(14.5, (float) $session->pages_recited);
    }

    public function test_apologised_session_records_no_recitation(): void
    {
        $session = RecitationSession::create([
            'quran_circle_id' => $this->circle()->id, 'held_on' => '2025-10-01', 'teacher_attendance' => TeacherAttendance::Apologized,
            'total_students' => 10, 'present_students' => 8, 'new_pages' => 4, 'revision_pages' => 4,
        ]);

        $this->assertEquals(0, (float) $session->pages_recited);
        $this->assertSame(0, $session->present_students);
    }

    public function test_stability_apology_and_compensation_rates(): void
    {
        $circle = $this->circle();
        $make = fn (array $a) => RecitationSession::create(['quran_circle_id' => $circle->id, 'held_on' => '2025-10-01', 'total_students' => 10, 'present_students' => 5, ...$a]);

        foreach (range(1, 8) as $_) {
            $make([]);
        }
        $make(['teacher_attendance' => TeacherAttendance::Apologized]);
        $make(['teacher_attendance' => TeacherAttendance::Apologized]);
        $make(['is_compensation' => true]);

        $k = (new QuranKpis(null))->summary();

        $this->assertSame(10, $k['scheduled']);
        $this->assertEquals(20.0, $k['apology_rate']);
        $this->assertEquals(50.0, $k['compensation_rate']);
        $this->assertEquals(90.0, $k['stability_index']); // (10 − 2 + 1) ÷ 10
    }

    public function test_circle_quality_keeps_decimal_precision(): void
    {
        $circle = $this->circle();
        $base = ['quran_circle_id' => $circle->id, 'evaluated_on' => '2025-10-01', 'interaction' => 3, 'punctuality' => 2, 'tajweed_rules' => 4, 'plan_achievement' => 4];
        CircleEvaluation::create([...$base, 'memorization_accuracy' => 5]);
        CircleEvaluation::create([...$base, 'memorization_accuracy' => 4]);

        $q = (new QuranKpis(null))->quality();

        $this->assertEquals(4.5, $q['criteria']->firstWhere('key', 'memorization_accuracy')['score']);
        $this->assertSame(['دقة الحفظ'], $q['strengths']);
        $this->assertSame(['الالتزام بالوقت'], $q['weaknesses']);
    }

    public function test_internal_activity_surplus_is_derived(): void
    {
        $semester = Semester::create(['academic_year' => '2025/2026', 'term' => Term::First]);
        $approved = ApprovedActivity::create(['semester_id' => $semester->id, 'team' => 'internal_affairs', 'name' => 'إفطار']);

        $activity = InternalActivity::create([
            'semester_id' => $semester->id, 'approved_activity_id' => $approved->id, 'held_on' => '2025-10-01',
            'cost_per_person' => 3, 'participants' => 40, 'revenue' => 120, 'actual_cost' => 135.5,
        ]);

        $this->assertEquals(-15.5, (float) $activity->net_result);
    }

    public function test_phone_numbers_are_encrypted_at_rest_and_masked(): void
    {
        $semester = Semester::create(['academic_year' => '2025/2026', 'term' => Term::First]);
        $activity = \App\Models\Activity::create(['semester_id' => $semester->id, 'name' => 'نشاط']);
        $guest = ActivityGuest::create(['activity_id' => $activity->id, 'name' => 'ضيف', 'phone' => '0791234567']);

        $raw = DB::table('activity_guests')->where('id', $guest->id)->value('phone');

        $this->assertNotSame('0791234567', $raw);
        $this->assertStringNotContainsString('0791234567', $raw);
        $this->assertSame('0791234567', $guest->fresh()->phone);
    }

    public function test_drive_links_are_recognised(): void
    {
        $this->assertSame('https://drive.google.com/file/d/1AbCdEfGhIjK/preview', DriveLink::embedUrl('https://drive.google.com/file/d/1AbCdEfGhIjK/view?usp=sharing'));
        $this->assertSame('https://drive.google.com/embeddedfolderview?id=1FolderIdXyZ12#grid', DriveLink::embedUrl('https://drive.google.com/drive/folders/1FolderIdXyZ12'));
        $this->assertSame('https://drive.google.com/file/d/1OpenIdForm123/preview', DriveLink::embedUrl('https://drive.google.com/open?id=1OpenIdForm123'));
        $this->assertNull(DriveLink::embedUrl('https://example.com/invoice.jpg'));
        $this->assertSame('079•••••67', PhoneMask::mask('0791234567'));
    }

    public function test_cached_indicators_refresh_when_data_changes(): void
    {
        $circle = $this->circle();
        $session = fn (float $new) => RecitationSession::create([
            'quran_circle_id' => $circle->id, 'held_on' => '2025-10-01',
            'total_students' => 10, 'present_students' => 10, 'new_pages' => $new, 'revision_pages' => 0,
        ]);

        $session(5);
        $this->assertEquals(5.0, (new QuranKpis(null))->summary()['pages_recited']);
        $this->assertEquals(5.0, (new QuranKpis(null))->summary()['pages_recited']); // served from cache

        $session(3);
        $this->assertEquals(8.0, (new QuranKpis(null))->summary()['pages_recited']); // cache invalidated by the save

        RecitationSession::latest('id')->first()->delete();
        $this->assertEquals(5.0, (new QuranKpis(null))->summary()['pages_recited']); // and by the delete
    }
}
