<?php

namespace App\Filament\Resources\Themes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ThemeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name.en')->label('Название (английский)')->required(),
            TextInput::make('name.de')->label('Название (немецкий)'),
            TextInput::make('name.fr')->label('Название (французский)'),
            TextInput::make('slug')->label('Адрес (slug)')->required()->unique(ignoreRecord: true),
            Textarea::make('description.en')->label('Описание (английский)'),
            Toggle::make('is_published')->label('Опубликована')->default(false),
            TextInput::make('sort_order')->label('Порядок')->numeric()->default(0),
        ]);
    }
}
