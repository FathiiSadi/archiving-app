<?php

namespace Database\Seeders;

use App\Enums\ActivityStatus;
use App\Enums\ArchiveStatus;
use App\Enums\Eligibility;
use App\Enums\Gender;
use App\Enums\GuestRole;
use App\Enums\InvitationStatus;
use App\Enums\LessonType;
use App\Enums\SessionMode;
use App\Enums\StaffRole;
use App\Enums\TeacherAttendance;
use App\Enums\Team;
use App\Enums\Term;
use App\Enums\UserRole;
use App\Enums\VendorType;
use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\ApprovedActivity;
use App\Models\InternalActivity;
use App\Models\QuranCircle;
use App\Models\QuranTeacher;
use App\Models\Semester;
use App\Models\TajweedLevel;
use App\Models\TajweedSection;
use App\Models\TajweedStaff;
use App\Models\TajweedStudent;
use App\Models\User;
use App\Models\Vendor;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Realistic sample data for trying the system:  php artisan db:seed --class=DemoSeeder
 *
 * Six semesters (2024/2025 → the current 2026/2027 first semester) for all four teams.
 * Nothing in the current semester is dated after today; later activities are "planned".
 * Every demo account uses the password "password". Not allowed when APP_ENV=production.
 */
class DemoSeeder extends Seeder
{
    private const DRIVE_FOLDER = 'https://drive.google.com/drive/folders/1DemoFolder';

    private const DRIVE_FILE = 'https://drive.google.com/file/d/1DemoInvoice';

    private const MALE = ['أحمد', 'محمد', 'عبدالله', 'عمر', 'يوسف', 'خالد', 'حمزة', 'أنس', 'معاذ', 'زيد', 'يزن', 'إبراهيم', 'مصطفى', 'بلال', 'سيف', 'طارق', 'هاشم', 'عبدالرحمن', 'كرم', 'ليث', 'مهند', 'قصي', 'راكان', 'باسل'];

    private const FEMALE = ['مريم', 'سارة', 'ليان', 'رهف', 'جنى', 'تسنيم', 'بيان', 'شهد', 'آمنة', 'هبة', 'نور', 'دانة', 'رغد', 'لمى', 'سلمى', 'ريم', 'أسيل', 'غدير', 'يارا', 'حلا', 'روان', 'ملك', 'إسراء', 'دعاء'];

    private const FAMILY = ['الخطيب', 'المومني', 'العزام', 'الحياري', 'البطاينة', 'القضاة', 'الزعبي', 'الشريف', 'النابلسي', 'الرفاعي', 'العتوم', 'الطراونة', 'الصمادي', 'الخوالدة', 'الكيلاني', 'العمري', 'الحوراني', 'الربابعة', 'الشياب', 'المحاميد', 'الجراح', 'الدويري', 'السعدي', 'القرالة'];

    private const ACTIVITIES = [
        'ملتقى الطلبة الجدد', 'أمسية قرآنية', 'محاضرة: فقه الأولويات', 'حملة تبرع بالدم', 'يوم تطوعي في المدينة',
        'مسابقة السيرة النبوية', 'دوري كرة القدم الخيري', 'معرض الكتاب الإسلامي', 'ندوة: الشباب وصناعة الأثر',
        'رحلة ثقافية إلى جرش', 'حملة كسوة الشتاء', 'ورشة مهارات الإلقاء', 'مسابقة حفظ الأربعين النووية',
        'إفطار صائم جماعي', 'ملتقى رمضان الإيماني', 'دورة إدارة الوقت', 'ندوة: الأسرة في الإسلام', 'حفل تكريم الحفّاظ',
        'زيارة دار المسنين', 'محاضرة: علوم القرآن',
    ];

    private const INTERNAL = ['إفطار جماعي للأعضاء', 'رحلة ترفيهية', 'لقاء تعارف الأعضاء', 'حفل تكريم المتطوعين', 'يوم رياضي للفريق', 'عشاء ختام الفصل', 'جلسة عصف ذهني للخطة', 'زيارة ميدانية'];

    private const LESSON_TOPICS = ['أحكام النون الساكنة والتنوين', 'أحكام الميم الساكنة', 'المدود وأنواعها', 'مخارج الحروف', 'صفات الحروف', 'الوقف والابتداء', 'أحكام الراء', 'التفخيم والترقيق', 'اللامات', 'الإدغام', 'القلقلة', 'همزة الوصل'];

    private CarbonImmutable $today;

    private Collection $types;

    private Collection $quranTeachers;

    private Collection $instructors;

    private Collection $supervisors;

    private Collection $levels;

    private Collection $vendors;

    private int $studentSerial = 0;

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DemoSeeder must not run in production.');
        }

        mt_srand(2026);
        $this->today = CarbonImmutable::today();

        $this->users();
        $this->lookups();

        foreach ($this->semesters() as $semester) {
            DB::transaction(function () use ($semester): void {
                $this->activities($semester);
                $this->quran($semester);
                $this->tajweed($semester);
                $this->internalAffairs($semester);
            });
            $this->command?->info("  ✓ {$semester->label}");
        }
    }

    // ------------------------------------------------------------------ helpers

    private function pick(array $items): mixed
    {
        return $items[mt_rand(0, count($items) - 1)];
    }

    private function chance(int $percent): bool
    {
        return mt_rand(1, 100) <= $percent;
    }

    private function person(?Gender $gender = null): array
    {
        $gender ??= $this->chance(50) ? Gender::Male : Gender::Female;
        $first = $this->pick($gender === Gender::Male ? self::MALE : self::FEMALE);

        return [$first.' '.$this->pick(self::FAMILY), $gender];
    }

    private function phone(): string
    {
        return '07'.$this->pick(['7', '8', '9']).mt_rand(1000000, 9999999);
    }

    private function link(string $base): string
    {
        return $base.substr(md5((string) mt_rand()), 0, 12).(str_contains($base, '/file/') ? '/view' : '');
    }

    private function isPast(CarbonImmutable $date): bool
    {
        return $date->lte($this->today);
    }

    /** Archiving is mostly complete for older semesters and still in progress for the current one. */
    private function archiveStatus(Semester $semester, bool $happened = true): ArchiveStatus
    {
        if (! $happened) {
            return ArchiveStatus::NotArchived;
        }

        $roll = mt_rand(1, 100);
        $complete = $semester->is_current ? 55 : 88;

        return match (true) {
            $roll <= $complete => ArchiveStatus::Complete,
            $roll <= $complete + 9 => ArchiveStatus::Incomplete,
            default => ArchiveStatus::NotArchived,
        };
    }

    /** Dates spread across the semester, never past its end. */
    private function dateIn(Semester $semester, float $fraction): CarbonImmutable
    {
        $start = CarbonImmutable::parse($semester->starts_on);
        $days = CarbonImmutable::parse($semester->ends_on)->diffInDays($start, absolute: true);

        return $start->addDays((int) round($days * $fraction));
    }

    // ------------------------------------------------------------------ base data

    private function users(): void
    {
        $accounts = [
            ['مدير النظام', 'admin@dal.test', UserRole::Admin],
            ['إدارة المبادرة', 'management@dal.test', UserRole::Management],
            ['قائد الأنشطة', 'activities@dal.test', UserRole::ActivitiesLeader],
            ['قائد التحفيظ', 'quran@dal.test', UserRole::QuranLeader],
            ['قائد التجويد', 'tajweed@dal.test', UserRole::TajweedLeader],
            ['قائد الشؤون الداخلية', 'internal@dal.test', UserRole::InternalAffairsLeader],
        ];

        foreach ($accounts as [$name, $email, $role]) {
            User::firstOrCreate(['email' => $email], ['name' => $name, 'password' => 'password', 'role' => $role]);
        }
    }

    private function lookups(): void
    {
        $this->types = collect(['ثقافي', 'ديني', 'اجتماعي', 'تطوعي', 'رياضي', 'تعليمي'])
            ->map(fn ($n) => ActivityType::firstOrCreate(['name' => $n]));

        $this->quranTeachers = collect(range(1, 8))->map(function (int $i) {
            [$name, $gender] = $this->person($i % 2 ? Gender::Male : Gender::Female);
            $title = $gender === Gender::Male ? 'الشيخ ' : 'الشيخة ';

            return QuranTeacher::firstOrCreate(['name' => $title.$name], [
                'gender' => $gender, 'phone' => $this->phone(), 'is_active' => $i <= 7,
            ]);
        });

        $this->instructors = collect(range(1, 6))->map(function (int $i) {
            [$name, $gender] = $this->person($i % 2 ? Gender::Male : Gender::Female);

            return TajweedStaff::firstOrCreate(['name' => 'أ. '.$name], [
                'role' => StaffRole::Instructor, 'gender' => $gender, 'phone' => $this->phone(),
            ]);
        });

        $this->supervisors = collect(range(1, 2))->map(function (int $i) {
            [$name, $gender] = $this->person($i === 1 ? Gender::Female : Gender::Male);

            return TajweedStaff::firstOrCreate(['name' => 'د. '.$name], [
                'role' => StaffRole::Supervisor, 'gender' => $gender, 'phone' => $this->phone(),
            ]);
        });

        $this->levels = collect(['المستوى الأول — التمهيدي', 'المستوى الثاني — المتوسط', 'المستوى الثالث — المتقدم', 'المستوى الرابع — الإجازة'])
            ->map(fn ($n, $i) => TajweedLevel::firstOrCreate(['name' => $n], ['sort_order' => $i + 1]));

        $this->vendors = collect([
            ['قاعة الياسمين', VendorType::Venue, 'قاعة مناسبات', 9, true],
            ['مطعم بيت الضيافة', VendorType::Vendor, 'ضيافة ووجبات', 8.5, true],
            ['حافلات الشمال', VendorType::Vendor, 'نقل', 4, false],
            ['منتزه الغابة', VendorType::Venue, 'رحلات', 7.5, true],
            ['مطبعة النور', VendorType::Vendor, 'طباعة', 8, true],
            ['حلويات الصفدي', VendorType::Vendor, 'ضيافة', 6, null],
            ['قاعة المدينة', VendorType::Venue, 'قاعة', 3.5, false],
            ['صوتيات الفجر', VendorType::Vendor, 'تجهيزات صوت', 9.5, true],
            ['مزرعة الريّان', VendorType::Venue, 'أنشطة خارجية', 7, true],
            ['مخبز الأمانة', VendorType::Vendor, 'وجبات', 5, null],
        ])->map(fn ($v) => Vendor::firstOrCreate(['name' => $v[0]], [
            'type' => $v[1], 'service' => $v[2], 'rating' => $v[3], 'suitable_for_future' => $v[4],
            'phone' => $this->phone(), 'contact_person' => $this->person()[0], 'invoice_url' => $this->link(self::DRIVE_FILE),
        ]));
    }

    /** @return array<Semester> */
    private function semesters(): array
    {
        $plan = [
            ['2024/2025', Term::First, '2024-09-15', '2025-01-16', 1800, 2000, 800, 1000],
            ['2024/2025', Term::Second, '2025-02-16', '2025-06-12', 1900, 2100, 850, 1000],
            ['2025/2026', Term::First, '2025-09-14', '2026-01-15', 2100, 2300, 900, 1100],
            ['2025/2026', Term::Second, '2026-02-15', '2026-06-11', 2200, 2400, 900, 1100],
            ['2025/2026', Term::Summer, '2026-06-21', '2026-08-13', 700, 800, 400, 500],
            ['2026/2027', Term::First, '2026-09-13', '2027-01-14', 2400, 2600, 1000, 1200],
        ];

        Semester::query()->update(['is_current' => false]);

        return collect($plan)->map(function (array $p) {
            [$year, $term, $start, $end, $actApproved, $actCap, $intApproved, $intCap] = $p;

            $semester = Semester::updateOrCreate(
                ['academic_year' => $year, 'term' => $term],
                ['starts_on' => $start, 'ends_on' => $end, 'tajweed_target_rate' => $term === Term::Summer ? 70 : 75],
            );
            $semester->budgets()->updateOrCreate(['team' => Team::Activities], ['approved_budget' => $actApproved, 'budget_cap' => $actCap]);
            $semester->budgets()->updateOrCreate(['team' => Team::InternalAffairs], ['approved_budget' => $intApproved, 'budget_cap' => $intCap]);

            return $semester;
        })->tap(function (Collection $all) {
            // The semester containing today (or the latest one) is the current semester.
            $current = $all->first(fn (Semester $s) => $this->today->betweenIncluded($s->starts_on, $s->ends_on)) ?? $all->last();
            $current->update(['is_current' => true]);
        })->map->fresh()->all();
    }

    // ------------------------------------------------------------------ teams

    private function activities(Semester $semester): void
    {
        $summer = $semester->term === Term::Summer;
        $names = collect(self::ACTIVITIES)->shuffle(mt_rand())->take($summer ? 6 : 12)->values();
        $suppliers = ['مطبعة النور', 'ضيافة الريّان', 'مكتبة الأقصى', 'صوتيات الفجر', 'حلويات الصفدي', 'مخبز الأمانة', 'دار الهدايا'];

        foreach ($names as $i => $name) {
            $approved = ApprovedActivity::firstOrCreate(
                ['semester_id' => $semester->id, 'team' => Team::Activities, 'name' => $name],
                ['estimated_budget' => mt_rand(8, 30) * 10],
            );

            $date = $this->dateIn($semester, ($i + 0.5) / $names->count());
            $happened = $this->isPast($date);
            $status = match (true) {
                ! $happened => ActivityStatus::Planned,
                $this->chance(8) => ActivityStatus::Cancelled,
                $this->chance(8) => ActivityStatus::Postponed,
                default => ActivityStatus::Completed,
            };
            $done = $status === ActivityStatus::Completed;
            $expected = mt_rand(4, 20) * 10;

            $activity = Activity::create([
                'semester_id' => $semester->id,
                'approved_activity_id' => $approved->id,
                'activity_type_id' => $this->types->random()->id,
                'name' => $name,
                'held_on' => $date,
                'location' => $this->pick(['مدرج الكلية', 'قاعة النشاطات', 'ساحة الجامعة', 'المسجد الجامعي', 'مركز المجتمع']),
                'status' => $status,
                'expected_attendance' => $expected,
                'actual_attendance' => $done ? (int) round($expected * mt_rand(65, 115) / 100) : null,
                'rating' => $done ? $this->pick([3, 4, 4, 4, 5, 5]) : null,
                'archive_status' => $this->archiveStatus($semester, $done),
                'drive_folder_url' => $done ? $this->link(self::DRIVE_FOLDER) : null,
            ]);

            if ($done) {
                foreach (range(1, mt_rand(1, 4)) as $p) {
                    $activity->purchases()->create([
                        'purchased_on' => $date->subDays(mt_rand(0, 5)),
                        'supplier' => $this->pick($suppliers),
                        'description' => $this->pick(['ضيافة', 'مطبوعات وشهادات', 'جوائز ودروع', 'تجهيزات صوت', 'لافتات', 'مواصلات']),
                        'quantity' => mt_rand(1, 60),
                        'total_cost' => mt_rand(15, 320),
                        'within_budget' => $this->chance(88),
                        'supplier_rating' => $this->pick([3, 4, 4, 5, 5]),
                        'invoice_url' => $this->link(self::DRIVE_FILE),
                    ]);
                }
            }

            $guestCount = mt_rand(0, 3);
            for ($g = 1; $g <= $guestCount; $g++) {
                [$guestName, $gender] = $this->person();
                $invitation = match (true) {
                    ! $happened => $this->pick([InvitationStatus::Invited, InvitationStatus::Approved, InvitationStatus::Approved]),
                    $status !== ActivityStatus::Completed => $this->pick([InvitationStatus::Apologized, InvitationStatus::RejectedByDeanship]),
                    default => $this->pick([InvitationStatus::Attended, InvitationStatus::Attended, InvitationStatus::Attended, InvitationStatus::NotAttended, InvitationStatus::Apologized, InvitationStatus::RejectedByDeanship]),
                };

                $activity->guests()->create([
                    'name' => $this->pick(['د. ', 'أ. ', 'م. ', 'الشيخ ']).$guestName,
                    'gender' => $gender,
                    'phone' => $this->phone(),
                    'role' => $g === 1 ? GuestRole::Speaker : GuestRole::Guest,
                    'affiliation' => $this->pick(['الجامعة الأردنية', 'جامعة اليرموك', 'وزارة الأوقاف', 'جمعية المحافظة على القرآن', 'الجامعة الهاشمية']),
                    'topic' => $g === 1 ? $name : null,
                    'invitation_status' => $invitation,
                ]);
            }
        }
    }

    private function quran(Semester $semester): void
    {
        $circleNames = ['حلقة الفجر', 'حلقة النور', 'حلقة الهدى', 'حلقة الإتقان', 'حلقة الفرقان', 'حلقة البيان', 'حلقة الريّان'];
        $count = $semester->term === Term::Summer ? 3 : 6;
        $teachers = $this->quranTeachers->where('is_active', true)->shuffle(mt_rand())->take($count)->values();

        foreach ($teachers as $i => $teacher) {
            $reliability = mt_rand(70, 98); // per-teacher chance of holding a session as scheduled
            $circle = QuranCircle::create([
                'semester_id' => $semester->id,
                'quran_teacher_id' => $teacher->id,
                'name' => $circleNames[$i],
                'capacity' => mt_rand(8, 18),
                'teacher_rating' => mt_rand(12, 20) / 2,
                'schedule' => $this->pick(['الأحد والثلاثاء 12:30', 'الاثنين والأربعاء 1:00', 'السبت 10:00', 'الخميس 11:30']),
                'location' => $this->pick(['المسجد الجامعي', 'قاعة 105', 'قاعة الحلقات']),
                'archive_status' => $this->archiveStatus($semester),
                'drive_folder_url' => $this->link(self::DRIVE_FOLDER),
            ]);

            $start = CarbonImmutable::parse($semester->starts_on)->addDays($i % 3);
            $weeks = (int) floor(CarbonImmutable::parse($semester->ends_on)->diffInWeeks($start, absolute: true));

            foreach (range(0, max(0, $weeks - 1)) as $w) {
                $date = $start->addWeeks($w);
                if (! $this->isPast($date)) {
                    break;
                }

                $apologized = ! $this->chance($reliability);
                $circle->sessions()->create([
                    'held_on' => $date,
                    'duration_minutes' => $this->pick([60, 75, 90]),
                    'mode' => $this->chance(20) ? SessionMode::Online : SessionMode::InPerson,
                    'teacher_attendance' => $apologized ? TeacherAttendance::Apologized : TeacherAttendance::Held,
                    'total_students' => $circle->capacity,
                    'present_students' => (int) round($circle->capacity * mt_rand(55, 100) / 100),
                    'new_pages' => mt_rand(4, 24) / 2,
                    'revision_pages' => mt_rand(10, 40) / 2,
                ]);

                $makeUp = $date->addDays(2);
                if ($apologized && $this->chance(65) && $this->isPast($makeUp)) {
                    $circle->sessions()->create([
                        'held_on' => $makeUp,
                        'duration_minutes' => 60,
                        'mode' => SessionMode::Online,
                        'teacher_attendance' => TeacherAttendance::Held,
                        'is_compensation' => true,
                        'total_students' => $circle->capacity,
                        'present_students' => (int) round($circle->capacity * mt_rand(45, 85) / 100),
                        'new_pages' => mt_rand(2, 10) / 2,
                        'revision_pages' => mt_rand(4, 20) / 2,
                    ]);
                }
            }

            foreach ([0.3, 0.7] as $point) {
                $date = $this->dateIn($semester, $point);
                if (! $this->isPast($date)) {
                    continue;
                }
                $circle->evaluations()->create([
                    'evaluated_on' => $date,
                    'memorization_accuracy' => mt_rand(3, 5),
                    'interaction' => mt_rand(3, 5),
                    'punctuality' => mt_rand(2, 5),
                    'tajweed_rules' => mt_rand(2, 4),
                    'plan_achievement' => mt_rand(2, 5),
                    'notes' => $this->chance(30) ? 'يُنصح بزيادة وقت المراجعة.' : null,
                ]);
            }
        }
    }

    private function tajweed(Semester $semester): void
    {
        $summer = $semester->term === Term::Summer;
        $levels = $summer ? $this->levels->take(2) : $this->levels;
        $sectionNo = 0;

        foreach ($levels as $level) {
            foreach (range(1, $summer || $level->sort_order === 4 ? 1 : 2) as $_) {
                $sectionNo++;
                $planned = $summer ? 8 : 16;
                $section = TajweedSection::create([
                    'semester_id' => $semester->id,
                    'tajweed_level_id' => $level->id,
                    'instructor_id' => $this->instructors->random()->id,
                    'supervisor_id' => $this->supervisors->random()->id,
                    'name' => "شعبة {$sectionNo} — ".$level->name,
                    'capacity' => 15,
                    'planned_lessons' => $planned,
                    'schedule' => $this->pick(['الاثنين 2:00', 'الثلاثاء 11:00', 'الأربعاء 1:00', 'الخميس 12:00']),
                    'archive_status' => $this->archiveStatus($semester),
                    'drive_folder_url' => $this->link(self::DRIVE_FOLDER),
                ]);

                $students = collect(range(1, mt_rand(8, 14)))->map(function () {
                    [$name, $gender] = $this->person();
                    $this->studentSerial++;

                    return TajweedStudent::create([
                        'name' => $name,
                        'gender' => $gender,
                        'phone' => $this->phone(),
                        'student_number' => '20'.str_pad((string) (240000 + $this->studentSerial), 6, '0', STR_PAD_LEFT),
                    ]);
                });

                $semesterOver = $this->isPast(CarbonImmutable::parse($semester->ends_on));
                foreach ($students as $student) {
                    $practical = mt_rand(50, 99);
                    $section->enrollments()->create([
                        'tajweed_student_id' => $student->id,
                        'recitation_score' => mt_rand(50, 99),
                        'practical_score' => $practical,
                        'final_exam_score' => $semesterOver ? mt_rand(45, 98) : null,
                        'eligibility' => ! $semesterOver ? Eligibility::Pending : ($practical >= 70 ? Eligibility::Eligible : Eligibility::NotEligible),
                    ]);
                }

                // Some sections fall short of their plan, so completion varies around the target.
                $delivered = (int) round($planned * mt_rand(60, 100) / 100);
                $start = CarbonImmutable::parse($semester->starts_on)->addDays(mt_rand(1, 4));
                $step = $summer ? 5 : 7;
                foreach (range(0, $delivered - 1) as $n) {
                    $date = $start->addDays($n * $step);
                    if (! $this->isPast($date)) {
                        break;
                    }
                    $type = $n % 2 ? LessonType::Practical : LessonType::Theoretical;
                    $section->lessons()->create([
                        'held_on' => $date,
                        'type' => $type,
                        'topic' => self::LESSON_TOPICS[$n % count(self::LESSON_TOPICS)],
                        'enrolled_count' => $students->count(),
                        'present_count' => (int) round($students->count() * mt_rand(60, 100) / 100),
                        'interaction_rating' => mt_rand(3, 5),
                        'correction_rating' => $type === LessonType::Practical ? mt_rand(3, 5) : null,
                    ]);
                }
            }
        }
    }

    private function internalAffairs(Semester $semester): void
    {
        $summer = $semester->term === Term::Summer;
        $names = collect(self::INTERNAL)->shuffle(mt_rand())->take($summer ? 3 : 6)->values();
        $runs = 0;

        foreach ($names as $i => $name) {
            $approved = ApprovedActivity::firstOrCreate(['semester_id' => $semester->id, 'team' => Team::InternalAffairs, 'name' => $name]);

            foreach (range(1, $i === 0 ? 2 : 1) as $_) {
                $date = $this->dateIn($semester, min(0.95, (++$runs - 0.5) / ($names->count() + 1)));
                if (! $this->isPast($date)) {
                    continue;
                }

                $participants = mt_rand(15, 70);
                $costPerPerson = $this->pick([0, 2, 3, 5, 7.5, 10]);
                $revenue = $costPerPerson * $participants;
                // Most activities roughly break even; some run a deficit or a surplus.
                $actualCost = round(max(20, $revenue) * mt_rand(80, 125) / 100, 3);

                InternalActivity::create([
                    'semester_id' => $semester->id,
                    'approved_activity_id' => $approved->id,
                    'vendor_id' => $this->vendors->random()->id,
                    'held_on' => $date,
                    'cost_per_person' => $costPerPerson,
                    'participants' => $participants,
                    'revenue' => $revenue,
                    'actual_cost' => $actualCost,
                    'final_rating' => $this->pick([3, 4, 4, 5, 5]),
                    'invoice_url' => $this->link(self::DRIVE_FILE),
                    'archive_status' => $this->archiveStatus($semester),
                    'drive_folder_url' => $this->link(self::DRIVE_FOLDER),
                ]);
            }
        }
    }
}
