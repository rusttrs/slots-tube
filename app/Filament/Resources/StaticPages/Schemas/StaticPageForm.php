<?php

namespace App\Filament\Resources\StaticPages\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class StaticPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title.en')->label('Заголовок (английский)')->required(),
            TextInput::make('title.de')->label('Заголовок (немецкий)'),
            TextInput::make('title.fr')->label('Заголовок (французский)'),
            TextInput::make('slug')->label('Адрес (slug)')->required()->unique(ignoreRecord: true),
            Textarea::make('body.en')->label('Текст (английский)')->rows(8),
            Toggle::make('is_published')->label('Опубликована')->default(false),
        ]);
    }
}
