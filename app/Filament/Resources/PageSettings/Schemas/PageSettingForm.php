<?php

namespace App\Filament\Resources\PageSettings\Schemas;

use App\Models\PageSetting;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class PageSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Страница')
                ->persistTabInQueryString()
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Основное')->schema([
                        Section::make('Страница')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('page_label')
                                    ->label('Название')
                                    ->state(fn (?PageSetting $record): string => $record?->label() ?? ''),
                                TextEntry::make('page_path')
                                    ->label('Адрес')
                                    ->state(fn (?PageSetting $record): string => $record?->path() ?? ''),
                                Toggle::make('noindex')
                                    ->label('Скрыть от поисковиков (noindex)')
                                    ->helperText('Страница останется доступной, но поисковики не будут её индексировать.')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                    Tab::make('Английский')->schema(self::localeFields('en', 'английский')),
                    Tab::make('Немецкий')->schema(self::localeFields('de', 'немецкий')),
                    Tab::make('Французский')->schema(self::localeFields('fr', 'французский')),
                ]),
        ]);
    }

    /**
     * @return array<int, Section>
     */
    private static function localeFields(string $locale, string $label): array
    {
        $fallback = $locale === 'en'
            ? 'Если пусто — используется стандартный текст сайта.'
            : 'Если пусто — используется английский вариант, а без него стандартный текст сайта.';

        return [
            Section::make("SEO ({$label})")
                ->description($fallback)
                ->schema([
                    TextInput::make("meta_title.{$locale}")
                        ->label('Meta title')
                        ->helperText('Заголовок во вкладке браузера и в выдаче. Оптимально 50–60 символов.')
                        ->maxLength(120),
                    Textarea::make("meta_description.{$locale}")
                        ->label('Meta description')
                        ->helperText('Описание в выдаче. Оптимально 140–160 символов.')
                        ->rows(3)
                        ->maxLength(300),
                ]),
            Section::make("FAQ ({$label})")
                ->description($locale === 'en'
                    ? 'Блок FAQ внизу страницы. Пустой список — блока на сайте не будет.'
                    : 'Если оставить пустым, на этой языковой версии покажутся английские вопросы.')
                ->visible(fn (?PageSetting $record): bool => (bool) $record?->hasFaq())
                ->schema([
                    Repeater::make("faq.{$locale}")
                        ->label('Вопросы и ответы')
                        ->schema([
                            TextInput::make('question')->label('Вопрос')->required()->maxLength(255),
                            Textarea::make('answer')->label('Ответ')->rows(3)->required(),
                        ])
                        ->defaultItems(0)
                        ->collapsible()
                        ->reorderable()
                        ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                        ->addActionLabel('Добавить вопрос'),
                ]),
        ];
    }
}
