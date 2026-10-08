<?php

namespace App\Console\Commands;

use App\Services\LikeService;
use Illuminate\Console\Command;

class SyncLikesCommand extends Command
{
    protected $signature = 'likes:sync';

    protected $description = 'Удаляет лайки окончательно удалённых записей и пересчитывает счётчики likes_count';

    public function handle(LikeService $likes): int
    {
        ['orphans' => $orphans, 'fixed' => $fixed] = $likes->sync();

        $this->info("Orphan likes removed: {$orphans}. Counters fixed: {$fixed}.");

        return self::SUCCESS;
    }
}
