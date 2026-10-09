<?php

namespace App\Filament\Resources\Bonuses\Schemas;

use App\Models\Bonus;
use App\Support\FilamentR2;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BonusForm
{
    /**
     * Поля карточки Where to play — как в ТЗ.
     *
     * @return array<int, mixed>
     */
    public static function cardFields(bool $withSort = false, bool $withPublished = true): array
    {
        $fields = [
            TextInput::make('casino_name')
                ->label('Название казино')
                ->required()
                ->helperText('Крупная надпись на карточке, как STAKE / LEOVEGAS.'),
            Textarea::make('short_text.en')
                ->label('Краткое описание бонуса (английский)')
                ->rows(2)
                ->helperText('Первая строка оффера, например «100% Bonus up to €1000».'),
            Textarea::make('short_text.de')->label('Краткое описание бонуса (немецкий)')->rows(2),
            Textarea::make('short_text.fr')->label('Краткое описание бонуса (французский)')->rows(2),
            TextInput::make('extra_text')
                ->label('Доп. описание бонуса')
                ->helperText('Вторая строка, например «+ 200 Free Spins». На странице бонусов обе строки — один жирный заголовок.'),
            TextInput::make('cta_url')
                ->label('Реф. ссылка')
                ->url()
                ->helperText('Оранжевая кнопка Get bonus на странице бонусов и Play now на странице слота. Текст кнопки не меняется.'),
            TextInput::make('website_url')
                ->label('Сайт казино')
                ->url()
                ->helperText('Красная ссылка «Go to website» под кнопкой Get bonus. Это не реферальная кнопка.'),
            CheckboxList::make('features')
                ->label('Плашки на карточке')
                ->options(fn (): array => Bonus::featureOptions())
                ->columns(2)
                ->bulkToggleable()
                ->helperText('Бейджи справа на карточке страницы бонусов: Regular offers, Live casino, Live chat, VIP program. На сайте подписи переводятся, иконки фиксированные.'),
            Toggle::make('is_hot')
                ->label('Hot')
                ->helperText('Оранжевая лента в углу карточки.')
                ->default(false),
            Toggle::make('is_new')
                ->label('New')
                ->helperText('Зелёная лента в углу. Если включены обе, на сайте показывается Hot.')
                ->default(false),
            Select::make('countries')
                ->label('Страны')
                ->multiple()
                ->searchable()
                ->options(fn (): array => Bonus::countryOptions())
                ->default([Bonus::allCountriesCode()])
                ->required()
                ->helperText('Показывать карточку посетителям этих стран (Cloudflare). Если для страны нет своих бонусов — на сайте подставляются карточки с «All countries».'),
        ];

        if ($withPublished) {
            $fields[] = Toggle::make('is_published')
                ->label('Показывать на сайте')
                ->default(true);
        }

        if ($withSort) {
            $fields[] = TextInput::make('sort_order')
                ->label('Порядок на странице слота')
                ->numeric()
                ->default(0)
                ->helperText('Чем меньше число, тем левее карточка. Порядок не меняется сам при обновлении страницы.');
        }

        return $fields;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            ...self::cardFields(),
            FilamentR2::prepare(
                FileUpload::make('logo_path')->label('Логотип (необязательно)')
                    ->directory('bonuses')
                    ->image()
                    ->imageEditor()
                    ->maxSize(5120)
                    ->helperText('Если пусто, на карточке будет название казино.')
            ),
            Toggle::make('no_kyc')
                ->label('Без KYC')
                ->helperText('Красная лента «No KYC!» на логотипе в карточке страницы бонусов.')
                ->default(false),
            Toggle::make('is_exclusive')
                ->label('Эксклюзив')
                ->helperText('Красная лента «Exclusive» на логотипе. Если включены обе, показывается «No KYC!».')
                ->default(false),
        ]);
    }
}
