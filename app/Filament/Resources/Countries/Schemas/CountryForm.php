<?php

namespace App\Filament\Resources\Countries\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CountryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label('Код')->length(2)->required()->unique(ignoreRecord: true),
            TextInput::make('name.en')->label('Название (английский)')->required(),
            TextInput::make('name.de')->label('Название (немецкий)'),
            TextInput::make('name.fr')->label('Название (французский)'),
            Toggle::make('is_active')->label('Активна')->default(true),
        ]);
    }
}
