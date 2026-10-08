<?php

namespace App\Support;

use App\Models\Slot;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * R2 — источник правды для загрузок в админке.
 * Пока нет публичного CDN (AWS_URL / cdn.slots.tube), зеркалим файлы
 * на локальный public disk, чтобы media_url() отдавал рабочие URL.
 */
class MediaMirror
{
    public static function mirrorSlotMedia(Slot $slot): void
    {
        foreach (self::pathsFromSlot($slot) as $path) {
            self::mirrorPath($path);
        }
    }

    public static function mirrorPath(string $path): bool
    {
        $path = ltrim($path, '/');
        if ($path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return false;
        }

        $public = Storage::disk('public');
        $r2 = Storage::disk('r2');

        try {
            if ($public->exists($path)) {
                return true;
            }
            if (! $r2->exists($path)) {
                return false;
            }

            return (bool) $public->put($path, $r2->get($path));
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return list<string>
     */
    public static function pathsFromSlot(Slot $slot): array
    {
        $paths = [];

        foreach (['cover_path', 'about_cover_path'] as $field) {
            if (filled($slot->{$field})) {
                $paths[] = (string) $slot->{$field};
            }
        }

        foreach (['screenshots', 'mobile_screenshots'] as $key) {
            $shots = is_array($slot->{$key}) ? $slot->{$key} : [];
            foreach ($shots as $shot) {
                if (! is_array($shot)) {
                    continue;
                }
                $path = $shot['path'] ?? $shot['image'] ?? null;
                if (is_array($path)) {
                    $path = $path[0] ?? null;
                }
                if (filled($path)) {
                    $paths[] = (string) $path;
                }
            }
        }

        foreach (['symbols', 'paytable_rows'] as $key) {
            $rows = is_array($slot->{$key}) ? $slot->{$key} : [];
            foreach ($rows as $row) {
                if (! is_array($row) || blank($row['image'] ?? null)) {
                    continue;
                }
                $image = $row['image'];
                if (is_array($image)) {
                    $image = $image[0] ?? null;
                }
                if (filled($image)) {
                    $paths[] = (string) $image;
                }
            }
        }

        return array_values(array_unique($paths));
    }
}
