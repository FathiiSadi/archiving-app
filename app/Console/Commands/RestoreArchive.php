<?php

namespace App\Console\Commands;

use App\Support\Backup\ArchiveBackup;
use Illuminate\Console\Command;

class RestoreArchive extends Command
{
    protected $signature = 'dal:restore {file? : Backup file name (default: the newest)} {--list : Only list the available backups} {--force : Skip the confirmation}';

    protected $description = 'Replace ALL archive data with the contents of a backup';

    public function handle(): int
    {
        $files = ArchiveBackup::files();

        if ($this->option('list')) {
            $this->line($files === [] ? 'No backups found.' : implode(PHP_EOL, $files));

            return self::SUCCESS;
        }

        $file = $this->argument('file') ?? ($files[0] ?? null);

        if ($file === null) {
            $this->error('No backups found.');

            return self::FAILURE;
        }

        $data = ArchiveBackup::read($file);
        $this->line("Backup {$file}, taken {$data['created_at']}, ".array_sum($data['counts']).' rows.');

        if (! $this->option('force') && ! $this->confirm('This DELETES all current data (including users) and replaces it with this backup. Continue?')) {
            $this->line('Cancelled.');

            return self::FAILURE;
        }

        $rows = ArchiveBackup::restore($file);
        $this->info("Restored {$rows} rows from {$file}.");

        return self::SUCCESS;
    }
}
