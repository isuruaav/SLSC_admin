<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use ZipArchive;

class CreateSiteBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'site:backup
                            {--db-only : Backup only the database}
                            {--files-only : Backup only uploaded media files}
                            {--keep=5 : Number of recent backup archives to keep}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a complete backup of the database and uploaded media assets.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting site backup process...');

        $timestamp = now()->format('Y-m-d_His');
        $backupDir = storage_path('app/backups');
        $tempDir = storage_path('app/backups/temp_' . $timestamp);

        if (! File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
            File::put($backupDir . '/.gitignore', "*\n!.gitignore\n");
        }

        File::makeDirectory($tempDir, 0755, true);

        $dbOnly = (bool) $this->option('db-only');
        $filesOnly = (bool) $this->option('files-only');
        $keep = (int) $this->option('keep');

        $sqlFile = null;

        try {
            // 1. Database Backup
            if (! $filesOnly) {
                $this->line('Dumping database...');
                $sqlFile = $tempDir . '/database.sql';
                $dumpSuccess = $this->dumpDatabase($sqlFile);

                if (! $dumpSuccess || ! File::exists($sqlFile)) {
                    $this->error('Failed to create database dump.');
                    File::deleteDirectory($tempDir);
                    return self::FAILURE;
                }

                $this->info('Database dump created: ' . number_format(File::size($sqlFile) / 1024, 2) . ' KB');
            }

            // 2. Archive Creation
            $zipFilename = 'backup_' . config('app.name', 'site') . '_' . $timestamp . '.zip';
            $zipPath = $backupDir . '/' . $zipFilename;

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                $this->error('Failed to create zip archive: ' . $zipPath);
                File::deleteDirectory($tempDir);
                return self::FAILURE;
            }

            if ($sqlFile && File::exists($sqlFile)) {
                $zip->addFile($sqlFile, 'database.sql');
            }

            // 3. Uploaded Files Backup
            if (! $dbOnly) {
                $this->line('Archiving uploaded media files...');
                $publicStorageDir = storage_path('app/public');

                if (File::isDirectory($publicStorageDir)) {
                    $files = File::allFiles($publicStorageDir);
                    $fileCount = 0;

                    foreach ($files as $file) {
                        $relativePath = 'storage/' . str_replace('\\', '/', $file->getRelativePathname());
                        $zip->addFile($file->getRealPath(), $relativePath);
                        $fileCount++;
                    }

                    $this->info("Added {$fileCount} media files to archive.");
                } else {
                    $this->warn('Public storage directory not found: ' . $publicStorageDir);
                }
            }

            $zip->close();

            // Clean temporary files
            File::deleteDirectory($tempDir);

            $zipSizeMb = number_format(File::size($zipPath) / (1024 * 1024), 2);
            $this->newLine();
            $this->info("Backup completed successfully!");
            $this->info("Archive: {$zipPath} ({$zipSizeMb} MB)");

            // 4. Prune old backups
            if ($keep > 0) {
                $this->pruneOldBackups($backupDir, $keep);
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Backup error: ' . $e->getMessage());
            if (File::isDirectory($tempDir)) {
                File::deleteDirectory($tempDir);
            }
            return self::FAILURE;
        }
    }

    /**
     * Dump database using mysqldump if available, or fallback to pure PHP PDO.
     */
    private function dumpDatabase(string $outputFile): bool
    {
        $mysqldumpPath = $this->findMysqldump();

        if ($mysqldumpPath !== null) {
            $dbConfig = config('database.connections.' . config('database.default'));
            $host = (string) ($dbConfig['host'] ?? '127.0.0.1');
            $port = (string) ($dbConfig['port'] ?? '3306');
            $database = (string) ($dbConfig['database'] ?? '');
            $username = (string) ($dbConfig['username'] ?? 'root');
            $password = (string) ($dbConfig['password'] ?? '');

            $cmd = [
                $mysqldumpPath,
                '--host=' . $host,
                '--port=' . $port,
                '--user=' . $username,
            ];

            if ($password !== '') {
                $cmd[] = '--password=' . $password;
            }

            $cmd = array_merge($cmd, [
                '--single-transaction',
                '--quick',
                '--skip-lock-tables',
                $database,
                '--result-file=' . $outputFile,
            ]);

            try {
                $process = new Process($cmd);
                $process->setTimeout(300);
                $process->run();

                if ($process->isSuccessful() && File::exists($outputFile) && File::size($outputFile) > 0) {
                    return true;
                }
            } catch (\Throwable) {
                // fallback to PDO
            }
        }

        // Fallback: Pure PDO export
        return $this->dumpWithPdo($outputFile);
    }

    /**
     * Fallback database dump using standard PDO connection.
     */
    private function dumpWithPdo(string $outputFile): bool
    {
        $handle = fopen($outputFile, 'w');
        if (! $handle) {
            return false;
        }

        fwrite($handle, "-- Site Database Backup\n");
        fwrite($handle, "-- Generated: " . now()->toIso8601String() . "\n");
        fwrite($handle, "SET foreign_key_checks = 0;\n\n");

        $connection = DB::connection();
        $databaseName = $connection->getDatabaseName();
        $tables = $connection->select('SHOW TABLES');

        $tableKey = 'Tables_in_' . $databaseName;

        foreach ($tables as $tableObj) {
            $tableName = $tableObj->$tableKey ?? current((array) $tableObj);
            if (! is_string($tableName) || $tableName === '') {
                continue;
            }

            // Drop & Create Table syntax
            fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");
            $createTable = $connection->select("SHOW CREATE TABLE `{$tableName}`");
            if (! empty($createTable)) {
                $createSql = ((array) $createTable[0])['Create Table'] ?? null;
                if ($createSql) {
                    fwrite($handle, $createSql . ";\n\n");
                }
            }

            // Export rows in chunks
            $rows = $connection->table($tableName)->get();
            if ($rows->isNotEmpty()) {
                foreach ($rows as $row) {
                    $rowArray = (array) $row;
                    $escapedValues = array_map(function ($value) use ($connection) {
                        if ($value === null) {
                            return 'NULL';
                        }
                        if (is_numeric($value)) {
                            return $value;
                        }
                        return $connection->getPdo()->quote((string) $value);
                    }, $rowArray);

                    $columns = array_map(fn ($col) => "`{$col}`", array_keys($rowArray));
                    $sql = "INSERT INTO `{$tableName}` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $escapedValues) . ");\n";
                    fwrite($handle, $sql);
                }
                fwrite($handle, "\n");
            }
        }

        fwrite($handle, "SET foreign_key_checks = 1;\n");
        fclose($handle);

        return true;
    }

    /**
     * Find mysqldump executable on Windows or Linux.
     */
    private function findMysqldump(): ?string
    {
        $candidates = [
            'mysqldump',
            'E:\\wamp64\\bin\\mysql\\mysql8.4.7\\bin\\mysqldump.exe',
            'C:\\wamp64\\bin\\mysql\\mysql8.4.7\\bin\\mysqldump.exe',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
        ];

        // Search wildcards in wamp directories if available
        $wampPaths = glob('E:/wamp64/bin/mysql/*/bin/mysqldump.exe');
        if (! empty($wampPaths)) {
            array_unshift($candidates, $wampPaths[0]);
        }

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Delete older backups, keeping only the most recent N files.
     */
    private function pruneOldBackups(string $backupDir, int $keep): void
    {
        $backups = File::glob($backupDir . '/backup_*.zip');

        if (count($backups) > $keep) {
            // Sort by modification time ascending (oldest first)
            usort($backups, fn ($a, $b) => filemtime($a) <=> filemtime($b));

            $toDelete = array_slice($backups, 0, count($backups) - $keep);
            foreach ($toDelete as $oldBackup) {
                File::delete($oldBackup);
                $this->line('Pruned old backup: ' . basename($oldBackup));
            }
        }
    }
}

