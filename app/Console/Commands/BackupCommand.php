<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use ZipArchive;

class BackupCommand extends Command
{
    protected $signature = 'app:backup
        {--only-db : Sauvegarde la base de données uniquement, sans les fichiers}
        {--keep=30 : Nombre de jours de rétention (défaut 30)}';

    protected $description = 'Sauvegarde BDD (mysqldump) + storage privé dans une archive zip horodatée';

    public function handle(): int
    {
        $start = microtime(true);
        $timestamp = now()->format('Y-m-d_His');
        $backupRoot = storage_path('app/backups');
        $tempDir = $backupRoot . DIRECTORY_SEPARATOR . '_tmp_' . $timestamp;
        $archivePath = $backupRoot . DIRECTORY_SEPARATOR . "tb-papa-{$timestamp}.zip";

        File::ensureDirectoryExists($backupRoot);
        File::ensureDirectoryExists($tempDir);

        $this->info("→ Sauvegarde TB-PAPA-CEEAC démarrée à {$timestamp}");

        $dumpPath = $tempDir . DIRECTORY_SEPARATOR . 'database.sql';
        $this->info('  • dump MySQL…');

        if (! $this->dumpDatabase($dumpPath)) {
            File::deleteDirectory($tempDir);

            return self::FAILURE;
        }

        $zip = new ZipArchive;
        if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error("Impossible de créer l'archive {$archivePath}");
            File::deleteDirectory($tempDir);

            return self::FAILURE;
        }

        $zip->addFile($dumpPath, 'database.sql');

        if (! $this->option('only-db')) {
            $this->info('  • ajout du storage privé…');
            $this->addDirectoryToZip($zip, storage_path('app/private'), 'storage/private');
            $this->addDirectoryToZip($zip, storage_path('app/public'), 'storage/public');
            if (File::exists(base_path('.env'))) {
                $zip->addFile(base_path('.env'), '.env');
            }
        }

        $zip->close();
        File::deleteDirectory($tempDir);

        $size = round(File::size($archivePath) / 1048576, 2);
        $elapsed = round(microtime(true) - $start, 2);
        $this->info("✓ Archive : {$archivePath} ({$size} Mo) en {$elapsed}s");

        $this->cleanOldBackups($backupRoot, (int) $this->option('keep'));

        return self::SUCCESS;
    }

    protected function dumpDatabase(string $outputPath): bool
    {
        $db = config('database.connections.' . config('database.default'));

        if (($db['driver'] ?? null) !== 'mysql') {
            $this->error('Seul le driver mysql est supporté.');

            return false;
        }

        $mysqldump = $this->resolveMysqldumpPath();
        $args = [
            $mysqldump,
            '--host=' . $db['host'],
            '--port=' . $db['port'],
            '--user=' . $db['username'],
            '--password=' . $db['password'],
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--default-character-set=utf8mb4',
            $db['database'],
        ];

        $result = Process::timeout(600)->run($args);

        if (! $result->successful()) {
            $this->error('Échec mysqldump : ' . $result->errorOutput());

            return false;
        }

        File::put($outputPath, $result->output());

        return true;
    }

    protected function resolveMysqldumpPath(): string
    {
        $candidates = [
            'C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqldump.exe',
            'C:\\laragon\\bin\\mysql\\mysql-8.4.0-winx64\\bin\\mysqldump.exe',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
        ];

        foreach ($candidates as $candidate) {
            if (File::exists($candidate)) {
                return $candidate;
            }
        }

        return 'mysqldump';
    }

    protected function addDirectoryToZip(ZipArchive $zip, string $directory, string $prefix): void
    {
        if (! File::isDirectory($directory)) {
            return;
        }

        $files = File::allFiles($directory);
        foreach ($files as $file) {
            $relative = $prefix . '/' . ltrim(str_replace($directory, '', $file->getPathname()), '/\\');
            $zip->addFile($file->getPathname(), str_replace('\\', '/', $relative));
        }
    }

    protected function cleanOldBackups(string $directory, int $keepDays): void
    {
        $cutoff = now()->subDays($keepDays);
        $removed = 0;

        foreach (File::files($directory) as $file) {
            if ($file->getExtension() !== 'zip') {
                continue;
            }
            if (Carbon::createFromTimestamp($file->getMTime())->lessThan($cutoff)) {
                File::delete($file->getPathname());
                $removed++;
            }
        }

        if ($removed > 0) {
            $this->info("  • {$removed} archive(s) périmée(s) supprimée(s) (rétention {$keepDays} j)");
        }
    }
}
