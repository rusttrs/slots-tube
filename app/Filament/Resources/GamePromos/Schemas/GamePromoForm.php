<?php

namespace App\Filament\Resources\GamePromos\Schemas;

use App\Models\Bonus;
use App\Support\FilamentR2;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class GamePromoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('casino_name')
                ->label('Название казино')
                ->required()
                ->helperText('Для списка в админке и alt логотипа.'),
            FilamentR2::prepare(
                FileUpload::make('logo_path')
                    ->label('Логотип')
                    ->directory('game-promos')
                    ->image()
                    ->imageEditor()
                    ->maxSize(5120)
                    ->required()
                    ->helperText('Квадратный логотип в верхней части попапа. Файл в Cloudflare R2.')
            ),
            Textarea::make('offer_text.en')
                ->label('Текст оффера (английский)')
                ->rows(2)
                ->required()
                ->helperText('Например: Welcome Package up to 375$ + 100 FS'),
            Textarea::make('offer_text.de')
                ->label('Текст оффера (немецкий)')
                ->rows(2),
            Textarea::make('offer_text.fr')
                ->label('Текст оффера (французский)')
                ->rows(2),
            TextInput::make('cta_url')
                ->label('Реф. ссылка')
                ->url()
                ->required()
                ->helperText('Кнопка Get Bonus.'),
            TextInput::make('cta_label.en')
                ->label('Текст кнопки (английский)')
                ->placeholder('Get Bonus')
                ->helperText('Пусто — подставится перевод с сайта.'),
            TextInput::make('cta_label.de')
                ->label('Текст кнопки (немецкий)')
                ->placeholder('Bonus holen'),
            TextInput::make('cta_label.fr')
                ->label('Текст кнопки (французский)')
                ->placeholder('Obtenir le bonus'),
            TextInput::make('legal_text.en')
                ->label('Юр. строка (английский)')
                ->placeholder('18+ | Terms Apply'),
            TextInput::make('legal_text.de')
                ->label('Юр. строка (немецкий)')
                ->placeholder('18+ | Es gelten die AGB'),
            TextInput::make('legal_text.fr')
                ->label('Юр. строка (французский)')
                ->placeholder('18+ | Conditions applicables'),
            Select::make('countries')
                ->label('Страны')
                ->multiple()
                ->searchable()
                ->options(fn (): array => Bonus::countryOptions())
                ->default([Bonus::allCountriesCode()])
                ->required()
                ->helperText('Попап для этих стран (Cloudflare). Если для страны нет своей записи — берётся «All countries».'),
            TextInput::make('delay_seconds')
                ->label('Задержка, сек')
                ->numeric()
                ->minValue(1)
                ->default(30)
                ->required()
                ->helperText('Сколько секунд после открытия демо ждать до показа попапа.'),
            TextInput::make('sort_order')
                ->label('Приоритет')
                ->numeric()
                ->default(0)
                ->helperText('Если несколько записей на одну страну — меньше число = выше приоритет.'),
            Toggle::make('is_published')
                ->label('Показывать на сайте')
                ->default(true),
        ]);
    }
}
