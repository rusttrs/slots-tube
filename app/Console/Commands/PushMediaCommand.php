<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PushMediaCommand extends Command
{
    protected $signature = 'media:push {directory : Папка внутри storage/app/public, например avatars}';

    protected $description = 'Выгружает в R2 локальные файлы из storage/app/public/{directory}, которых там ещё нет';

    public function handle(): int
    {
        $directory = trim((string) $this->argument('directory'), '/');
        if ($directory === '') {
            $this->error('Укажите папку, например: php artisan media:push avatars');

            return self::INVALID;
        }

        $public = Storage::disk('public');
        $r2 = Storage::disk('r2');

        $files = array_values(array_filter(
            $public->allFiles($directory),
            fn (string $path): bool => ! str_starts_with(basename($path), '.'),
        ));
        $this->info("Local files in {$directory}/: ".count($files));

        $uploaded = 0;
        $skipped = 0;
        $failed = 0;
        foreach ($files as $path) {
            try {
                if ($r2->exists($path)) {
                    $skipped++;

                    continue;
                }
                if ($r2->put($path, (string) $public->get($path))) {
                    $uploaded++;

                    continue;
                }
            } catch (Throwable) {
                // counted as failed below
            }

            $this->warn("  failed: {$path}");
            $failed++;
        }

        $this->info("Done. Uploaded: {$uploaded}, already in R2: {$skipped}, failed: {$failed}.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
