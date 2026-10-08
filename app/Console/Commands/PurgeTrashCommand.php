<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurgeTrashCommand extends Command
{
    protected $signature = 'trash:purge {--days= : Override retention days}';

    protected $description = 'Окончательно удаляет записи из корзины старше N дней';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('trash.retention_days', 30));
        $days = max(1, $days);
        $before = now()->subDays($days);

        $this->info("Purge soft-deleted records older than {$days} day(s) (before {$before}).");

        /** @var array<string, class-string<Model>> $models */
        $models = config('trash.models', []);
        $total = 0;

        foreach ($models as $key => $class) {
            if (! class_exists($class) || ! in_array(SoftDeletes::class, class_uses_recursive($class), true)) {
                $this->warn("Skip {$key}: SoftDeletes not enabled.");

                continue;
            }

            $query = $class::onlyTrashed()->where('deleted_at', '<', $before);
            $count = (clone $query)->count();
            if ($count === 0) {
                continue;
            }

            $query->forceDelete();
            $total += $count;
            $this->line("  {$key}: {$count}");
        }

        $this->info("Done. Purged {$total} record(s).");

        return self::SUCCESS;
    }
}
