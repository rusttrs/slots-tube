<?php

namespace App\Filament\Resources\Providers\Schemas;

use App\Support\FilamentR2;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProviderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name.en')->label('Название (английский)')->required(),
            TextInput::make('name.de')->label('Название (немецкий)'),
            TextInput::make('name.fr')->label('Название (французский)'),
            TextInput::make('slug')->label('Адрес (slug)')->required()->unique(ignoreRecord: true),
            Textarea::make('description.en')->label('Описание (английский)'),
            FilamentR2::prepare(
                FileUpload::make('logo_path')->label('Логотип')
                    ->directory('providers')
                    ->image()
                    ->imageEditor()
                    ->maxSize(5120)
                    ->helperText('Карточка 340×172 на странице слота, оранжевый фон как в макете. Клик ведёт на страницу провайдера. Загруженный файл показывается в админке и на сайте.')
            ),
            Toggle::make('is_published')->label('Опубликован')->default(false),
            TextInput::make('sort_order')->label('Порядок')->numeric()->default(0),
        ]);
    }
}
