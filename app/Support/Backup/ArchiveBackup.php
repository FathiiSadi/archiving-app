<?php

namespace App\Support\Backup;

use App\Support\Kpi\KpiCache;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Regular data backups (BR-17).
 *
 * A backup is one gzipped JSON file holding every archive table, read straight from the
 * database: phone numbers stay encrypted exactly as stored (APP_KEY is still needed to read
 * them), and no external tool such as pg_dump is required. The file contains financial data
 * and password hashes, so the backup disk must stay private.
 */
final class ArchiveBackup
{
    /** Every archive table, parents before children (the order rows can be re-inserted in). */
    public const TABLES = [
        'users',
        'semesters', 'semester_budgets', 'approved_activities', 'activity_types',
        'tajweed_staff', 'tajweed_levels', 'tajweed_sections', 'tajweed_lessons', 'tajweed_students', 'tajweed_enrollments',
        'activities', 'activity_purchases', 'activity_guests',
        'vendors', 'internal_activities',
        'quran_teachers', 'quran_circles', 'recitation_sessions', 'circle_evaluations',
    ];

    /** Framework tables that hold no archive data. */
    private const IGNORED = [
        'migrations', 'cache', 'cache_locks', 'sessions', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens',
    ];

    public static function disk(): Filesystem
    {
        return Storage::disk(config('backup.disk'));
    }

    /** @return array{path: string, rows: int, bytes: int} */
    public static function create(): array
    {
        self::assertEveryTableIsListed();

        $tables = [];
        $rows = 0;

        // One read-only snapshot so related tables are consistent with each other.
        DB::transaction(function () use (&$tables, &$rows): void {
            foreach (self::TABLES as $table) {
                $tables[$table] = DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
                $rows += count($tables[$table]);
            }
        });

        $payload = gzencode(json_encode([
            'app' => 'dal-archive',
            'format' => 1,
            'created_at' => now()->toIso8601String(),
            'counts' => array_map('count', $tables),
            'tables' => $tables,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 9);

        $path = 'dal-backup-'.now()->format('Y-m-d_His').'.json.gz';

        if (! self::disk()->put($path, $payload, 'private')) {
            throw new RuntimeException("Could not write the backup file [{$path}].");
        }

        return ['path' => $path, 'rows' => $rows, 'bytes' => strlen($payload)];
    }

    /** Delete the oldest backups, keeping the newest $keep. @return list<string> deleted paths */
    public static function prune(int $keep): array
    {
        $files = self::files();
        $old = $keep > 0 ? array_slice($files, $keep) : [];

        foreach ($old as $path) {
            self::disk()->delete($path);
        }

        return $old;
    }

    /** @return list<string> backup files, newest first */
    public static function files(): array
    {
        $files = array_values(array_filter(
            self::disk()->files(),
            fn (string $path) => preg_match('/^dal-backup-[\d_-]+\.json\.gz$/', $path) === 1,
        ));

        rsort($files);

        return $files;
    }

    /** @return array{created_at: string, counts: array<string, int>, tables: array<string, list<array<string, mixed>>>} */
    public static function read(string $path): array
    {
        $raw = self::disk()->get($path);

        if ($raw === null) {
            throw new RuntimeException("Backup file [{$path}] was not found.");
        }

        $data = json_decode((string) gzdecode($raw), true, flags: JSON_THROW_ON_ERROR);

        if (($data['app'] ?? null) !== 'dal-archive' || ($data['format'] ?? null) !== 1) {
            throw new RuntimeException("[{$path}] is not a DAL archive backup.");
        }

        if (array_keys($data['tables']) !== self::TABLES) {
            throw new RuntimeException("[{$path}] does not match the current list of tables.");
        }

        return $data;
    }

    /** Replace everything in the archive with the contents of a backup. @return int rows restored */
    public static function restore(string $path): int
    {
        $data = self::read($path);
        self::assertEveryTableIsListed();
        $rows = 0;

        DB::transaction(function () use ($data, &$rows): void {
            foreach (array_reverse(self::TABLES) as $table) {
                DB::table($table)->delete();
            }

            foreach (self::TABLES as $table) {
                foreach (array_chunk($data['tables'][$table], 200) as $chunk) {
                    DB::table($table)->insert($chunk);
                    $rows += count($chunk);
                }

                if (DB::getDriverName() === 'pgsql') {
                    DB::statement("select setval(pg_get_serial_sequence('{$table}', 'id'), coalesce(max(id), 1), max(id) is not null) from \"{$table}\"");
                }
            }
        });

        KpiCache::flush();

        return $rows;
    }

    /** A new table that is missing from TABLES would silently be left out of backups — fail loudly instead. */
    private static function assertEveryTableIsListed(): void
    {
        $existing = array_map(
            fn (string $table) => str($table)->afterLast('.')->toString(),
            Schema::getTableListing(schemaQualified: false),
        );

        $unknown = array_diff($existing, self::TABLES, self::IGNORED);
        $missing = array_diff(self::TABLES, $existing);

        if ($unknown !== [] || $missing !== []) {
            throw new RuntimeException(
                'The backup table list is out of date. Not covered: ['.implode(', ', $unknown).']. Not in database: ['.implode(', ', $missing).'].'
            );
        }
    }
}
