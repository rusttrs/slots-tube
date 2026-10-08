<?php

namespace App\Console\Commands;

use App\Support\MediaMirror;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MirrorMediaCommand extends Command
{
    protected $signature = 'media:mirror';

    protected $description = 'Копирует все файлы из R2 в локальное зеркало storage/app/public (после переезда на новый сервер)';

    public function handle(): int
    {
        $files = Storage::disk('r2')->allFiles();
        $this->info('R2 objects: '.count($files));

        $failed = 0;
        foreach ($files as $path) {
            if (! MediaMirror::mirrorPath($path)) {
                $this->warn("  failed: {$path}");
                $failed++;
            }
        }

        $this->info('Done. Mirrored: '.(count($files) - $failed).", failed: {$failed}.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
