<?php

namespace App\Console\Commands;

use App\Support\Backup\ArchiveBackup;
use Illuminate\Console\Command;
use Illuminate\Support\Number;

class BackupArchive extends Command
{
    protected $signature = 'dal:backup {--keep= : How many backups to keep (default: config backup.keep)}';

    protected $description = 'Back up all archive data to the backup disk and prune old backups (BR-17)';

    public function handle(): int
    {
        $backup = ArchiveBackup::create();
        $this->info("Backup written: {$backup['path']} ({$backup['rows']} rows, ".Number::fileSize($backup['bytes']).')');

        $deleted = ArchiveBackup::prune((int) ($this->option('keep') ?? config('backup.keep')));

        if ($deleted !== []) {
            $this->line('Removed '.count($deleted).' old backup(s).');
        }

        return self::SUCCESS;
    }
}
