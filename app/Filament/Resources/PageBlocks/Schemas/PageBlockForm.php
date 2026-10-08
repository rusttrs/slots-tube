<?php

namespace App\Filament\Resources\PageBlocks\Schemas;

use App\Support\FilamentR2;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PageBlockForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('key')->label('Ключ')->required(),
            TextInput::make('page')->label('Страница'),
            TextInput::make('title.en')->label('Заголовок (английский)'),
            Textarea::make('body.en')->label('Текст (английский)'),
            FilamentR2::prepare(
                FileUpload::make('image_path')->label('Картинка')
                    ->directory('page-blocks')
                    ->image()
                    ->imageEditor()
                    ->maxSize(5120)
            ),
            Toggle::make('is_published')->label('Опубликован')->default(true),
            TextInput::make('sort_order')->label('Порядок')->numeric()->default(0),
        ]);
    }
}
