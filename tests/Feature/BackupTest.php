<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\QuranTeacher;
use App\Models\User;
use App\Models\Vendor;
use App\Support\Backup\ArchiveBackup;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('backups');
        $this->seed(DemoSeeder::class);
    }

    public function test_backup_covers_every_table_and_restores_exactly(): void
    {
        $before = collect(ArchiveBackup::TABLES)->mapWithKeys(fn ($t) => [$t => DB::table($t)->orderBy('id')->get()->toArray()]);

        $this->artisan('dal:backup')->assertSuccessful();
        $file = ArchiveBackup::files()[0];

        // Damage the data: delete, change and add records.
        Activity::query()->first()->delete();
        Vendor::query()->first()->update(['name' => 'تم التغيير']);
        User::query()->where('email', 'quran@dal.test')->delete();
        QuranTeacher::create(['name' => 'معلم بعد النسخة']);

        $this->artisan('dal:restore', ['file' => $file, '--force' => true])->assertSuccessful();

        foreach (ArchiveBackup::TABLES as $table) {
            $this->assertEquals($before[$table], DB::table($table)->orderBy('id')->get()->toArray(), "Table {$table} differs after restore.");
        }

        // New records can still be created after a restore (id sequences are intact).
        $this->assertNotNull(QuranTeacher::create(['name' => 'معلم جديد'])->id);
    }

    public function test_phone_numbers_stay_encrypted_inside_the_backup(): void
    {
        $teacher = QuranTeacher::create(['name' => 'اختبار', 'phone' => '0791234567']);

        $this->artisan('dal:backup')->assertSuccessful();
        $raw = gzdecode(Storage::disk('backups')->get(ArchiveBackup::files()[0]));

        $this->assertStringNotContainsString('0791234567', $raw);
        $this->assertStringContainsString($teacher->getRawOriginal('phone') ?? DB::table('quran_teachers')->where('id', $teacher->id)->value('phone'), $raw);
    }

    public function test_old_backups_are_pruned(): void
    {
        foreach (['2026-01-01_020000', '2026-01-02_020000', '2026-01-03_020000'] as $stamp) {
            Storage::disk('backups')->put("dal-backup-{$stamp}.json.gz", 'x');
        }
        Storage::disk('backups')->put('unrelated.txt', 'keep me');

        $this->artisan('dal:backup', ['--keep' => 2])->assertSuccessful();

        $this->assertCount(2, ArchiveBackup::files());
        $this->assertNotContains('dal-backup-2026-01-01_020000.json.gz', ArchiveBackup::files());
        Storage::disk('backups')->assertExists('unrelated.txt');
    }

    public function test_restore_rejects_files_that_are_not_backups(): void
    {
        Storage::disk('backups')->put('dal-backup-2026-01-01_020000.json.gz', gzencode(json_encode(['app' => 'other'])));

        $this->expectException(\RuntimeException::class);
        ArchiveBackup::restore('dal-backup-2026-01-01_020000.json.gz');
    }
}
