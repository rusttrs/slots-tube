<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trash
{
    /**
     * @return array<string, class-string<Model>>
     */
    public static function models(): array
    {
        /** @var array<string, class-string<Model>> $models */
        $models = config('trash.models', []);

        return $models;
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        /** @var array<string, string> $labels */
        $labels = config('trash.labels', []);

        return $labels;
    }

    public static function retentionDays(): int
    {
        return max(1, (int) config('trash.retention_days', 30));
    }

    public static function totalCount(): int
    {
        $total = 0;

        foreach (self::models() as $class) {
            if (! class_exists($class) || ! in_array(SoftDeletes::class, class_uses_recursive($class), true)) {
                continue;
            }

            $total += $class::onlyTrashed()->count();
        }

        return $total;
    }

    public static function labelFor(Model $record): string
    {
        if (method_exists($record, 'displayTitle')) {
            $value = (string) $record->displayTitle();
            if ($value !== '') {
                return $value;
            }
        }

        if (method_exists($record, 'displayName')) {
            $value = (string) $record->displayName();
            if ($value !== '') {
                return $value;
            }
        }

        foreach (['casino_name', 'email', 'slug', 'key', 'code'] as $field) {
            if (filled($record->{$field} ?? null)) {
                return (string) $record->{$field};
            }
        }

        if (isset($record->title)) {
            if (is_array($record->title)) {
                return (string) ($record->title['en'] ?? $record->title['de'] ?? $record->title['fr'] ?? reset($record->title) ?: '#'.$record->getKey());
            }

            return (string) $record->title;
        }

        if (isset($record->name)) {
            if (is_array($record->name)) {
                return (string) ($record->name['en'] ?? $record->name['de'] ?? $record->name['fr'] ?? reset($record->name) ?: '#'.$record->getKey());
            }

            return (string) $record->name;
        }

        if (isset($record->body) && is_string($record->body)) {
            return \Illuminate\Support\Str::limit(strip_tags($record->body), 60);
        }

        return '#'.$record->getKey();
    }
}
