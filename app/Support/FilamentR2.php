<?php

namespace App\Support;

use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * R2 — хранение; превью в Filament — через локальное зеркало /storage,
 * потому что signed URL R2 без CORS ломают FilePond («Ожидание» / «Укажите размер»).
 */
class FilamentR2
{
    public static function prepare(FileUpload $upload): FileUpload
    {
        return $upload
            ->disk('r2')
            ->visibility('private')
            ->fetchFileInformation(false)
            ->getUploadedFileUsing(function (BaseFileUpload $component, string $file, string|array|null $storedFileNames): ?array {
                $file = ltrim($file, '/');
                if ($file === '' || str_starts_with($file, 'http://') || str_starts_with($file, 'https://')) {
                    return null;
                }

                MediaMirror::mirrorPath($file);

                $name = basename($file);
                if (is_array($storedFileNames)) {
                    $name = $storedFileNames[$file] ?? $name;
                } elseif (filled($storedFileNames)) {
                    $name = (string) $storedFileNames;
                }

                $public = Storage::disk('public');
                $size = 0;
                $type = null;

                try {
                    if ($public->exists($file)) {
                        $size = (int) $public->size($file);
                        $type = $public->mimeType($file);
                    }
                } catch (Throwable) {
                    // leave defaults
                }

                $url = $public->exists($file)
                    ? self::publicPreviewUrl($file)
                    : (function_exists('media_url') ? media_url($file) : $component->getDisk()->url($file));

                return [
                    'name' => $name,
                    'size' => $size,
                    'type' => $type,
                    'url' => Str::sanitizeUrl($url),
                ];
            });
    }

    /**
     * Путь без хоста: FilePond качает превью через fetch, и абсолютный URL из APP_URL
     * ломается, если админку открыли на другом хосте/порту (www, localhost:8011).
     */
    public static function publicPreviewUrl(string $file): string
    {
        $url = Storage::disk('public')->url(ltrim($file, '/'));

        return '/'.ltrim((string) parse_url($url, PHP_URL_PATH), '/');
    }
}
