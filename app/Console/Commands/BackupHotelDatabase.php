<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class BackupHotelDatabase extends Command
{
    protected $signature = 'hotel:backup {--keep=14 : Number of recent backups to retain}';

    protected $description = 'Create a compressed logical backup of the hotel database';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $directory = 'backups';
        $filename = 'hotel-'.now()->format('Ymd-His').'.jsonl.gz';
        $relativePath = $directory.'/'.$filename;
        $absolutePath = $disk->path($relativePath);

        $disk->makeDirectory($directory);
        $stream = gzopen($absolutePath, 'wb9');
        if ($stream === false) {
            $this->error('Unable to open the backup file for writing.');

            return self::FAILURE;
        }

        try {
            gzwrite($stream, json_encode([
                'type' => 'metadata',
                'created_at' => now()->toIso8601String(),
                'connection' => DB::getDriverName(),
                'application' => config('app.name'),
            ], JSON_THROW_ON_ERROR).PHP_EOL);

            foreach (Schema::getTableListing() as $table) {
                if ($table === 'migrations') {
                    continue;
                }

                DB::table($table)
                    ->orderBy($this->firstColumn($table))
                    ->chunk(500, function ($rows) use ($stream, $table): void {
                        foreach ($rows as $row) {
                            gzwrite($stream, json_encode([
                                'type' => 'row',
                                'table' => $table,
                                'data' => (array) $row,
                            ], JSON_THROW_ON_ERROR).PHP_EOL);
                        }
                    });
            }
        } catch (Throwable $exception) {
            gzclose($stream);
            $disk->delete($relativePath);
            report($exception);
            $this->error('Backup failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        gzclose($stream);
        $this->pruneOldBackups($disk, max(1, (int) $this->option('keep')));
        $this->info('Database backup created: storage/app/private/'.$relativePath);

        return self::SUCCESS;
    }

    private function firstColumn(string $table): string
    {
        return Schema::getColumnListing($table)[0] ?? 'created_at';
    }

    private function pruneOldBackups($disk, int $keep): void
    {
        $backups = collect($disk->files('backups'))
            ->filter(fn (string $path): bool => str_ends_with($path, '.jsonl.gz'))
            ->sortDesc()
            ->values();

        $backups->slice($keep)->each(fn (string $path) => $disk->delete($path));
    }
}
