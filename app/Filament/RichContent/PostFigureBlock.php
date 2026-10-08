<?php

namespace App\Filament\RichContent;

use App\Support\FilamentR2;
use App\Support\MediaMirror;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class PostFigureBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'figure';
    }

    public static function getLabel(): string
    {
        return 'Картинка с подписью';
    }

    public static function getIcon(): string|\BackedEnum|Htmlable|null
    {
        return Heroicon::OutlinedPhoto;
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalHeading('Картинка с подписью')
            ->modalDescription('Подпись видна под картинкой на странице, например: Jackpot cash © Unsplash - ben frost.')
            ->schema([
                FilamentR2::prepare(
                    FileUpload::make('image')
                        ->label('Картинка')
                        ->image()
                        ->directory('posts/body')
                        ->maxSize(5120)
                        ->required()
                ),
                TextInput::make('caption')
                    ->label('Подпись')
                    ->maxLength(300),
            ]);
    }

    public static function getPreviewLabel(array $config): string
    {
        $caption = trim((string) ($config['caption'] ?? ''));

        return $caption !== '' ? $caption : 'Картинка';
    }

    public static function toPreviewHtml(array $config): ?string
    {
        return self::toHtml($config, []);
    }

    public static function toHtml(array $config, array $data): ?string
    {
        $path = self::path($config['image'] ?? null);
        if ($path === null) {
            return null;
        }

        MediaMirror::mirrorPath($path);
        $src = e(media_url($path));
        $caption = trim((string) ($config['caption'] ?? ''));
        $alt = e($caption);
        $image = '<img src="'.$src.'" alt="'.$alt.'" loading="lazy">';

        if ($caption === '') {
            return '<figure>'.$image.'</figure>';
        }

        return '<figure>'.$image.'<figcaption>'.$alt.'</figcaption></figure>';
    }

    private static function path(mixed $image): ?string
    {
        if (is_array($image)) {
            $image = $image[0] ?? null;
        }

        if (! is_string($image) || $image === '') {
            return null;
        }

        return ltrim($image, '/');
    }
}
