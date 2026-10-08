<?php

/**
 * Только нужные оверрайды. Остальное — дефолты Livewire.
 * Temp-загрузки НЕльзя класть на R2 (default disk): Filament потом
 * не может стабильно перенести файл на целевой диск → «Ошибка при загрузке».
 */
return [
    'temporary_file_upload' => [
        'disk' => env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK', 'local'),
        'rules' => null,
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 5,
        'cleanup' => true,
    ],
];
