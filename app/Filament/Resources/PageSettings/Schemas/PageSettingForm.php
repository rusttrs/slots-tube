<?php

namespace App\Filament\Resources\PageSettings\Schemas;

use App\Models\Author;
use App\Models\PageSetting;
use App\Support\FilamentR2;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

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
                    Tab::make('Группы авторов')
                        ->visible(fn (?PageSetting $record): bool => (bool) $record?->hasTeamSections())
                        ->schema([self::teamSectionsField()]),
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
            Section::make("Тексты страницы ({$label})")
                ->description($locale === 'en'
                    ? 'Пусто — текст из макета.'
                    : 'Пусто — английский вариант, а без него текст из макета.')
                ->visible(fn (?PageSetting $record): bool => $record !== null && $record->textFields() !== [])
                ->schema(fn (?PageSetting $record): array => self::textFields($record, $locale)),
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
            Section::make("Блоки описания ({$label})")
                ->description($locale === 'en'
                    ? 'Белые блоки между списком и FAQ. Элемент «Новая секция» начинает новый блок, элементы после него попадают внутрь. Пустой список — блоков на сайте не будет.'
                    : 'Если оставить пустым, на этой языковой версии покажутся английские блоки.')
                ->visible(fn (?PageSetting $record): bool => (bool) $record?->hasBlocks())
                ->collapsible()
                ->schema([self::blocksField($locale)]),
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

    private static function blocksField(string $locale): Builder
    {
        $markup = 'Форматирование: **жирный**, [текст ссылки](/content/guides/). Абзацы — через пустую строку.';
        $lines = 'Каждый пункт с новой строки. Жирное начало пункта: **Название:** текст.';
        $image = fn (string $name, string $label, ?string $help = null): FileUpload => FilamentR2::prepare(
            FileUpload::make($name)->label($label)->directory('page-about')->image()->maxSize(5120)->helperText($help)
        );
        $label = fn (string $name, string $field = 'title') => fn (?array $state): string => $name.(filled($state[$field] ?? null) ? ' · '.Str::limit((string) $state[$field], 60) : '');

        return Builder::make("blocks.{$locale}")
            ->hiddenLabel()
            ->blocks([
                Block::make('section')
                    ->label($label('▌ Новая секция'))
                    ->icon('heroicon-o-rectangle-group')
                    ->schema([
                        TextInput::make('title')->label('Заголовок секции (H2)')->maxLength(160),
                        $image('hero_image', 'Картинка во всю ширину сверху', 'Как баннер «Games» в макете, 1180×202. Необязательно.'),
                        Textarea::make('pills')->label('Плашки под картинкой')->rows(3)->maxLength(1000)->helperText('Каждая плашка с новой строки, как «100% Winnings Protection». Необязательно.'),
                    ]),
                Block::make('text')
                    ->label($label('Текст', 'text'))
                    ->icon('heroicon-o-bars-3-bottom-left')
                    ->schema([
                        Textarea::make('text')->label('Текст')->rows(4)->required()->maxLength(5000)->helperText($markup),
                        Toggle::make('lead')->label('Вводный абзац (чуть крупнее)'),
                    ]),
                Block::make('heading')
                    ->label($label('Подзаголовок', 'text'))
                    ->icon('heroicon-o-hashtag')
                    ->schema([
                        TextInput::make('text')->label('Текст')->required()->maxLength(160),
                        Radio::make('level')->label('Вид')->options(['h3' => 'Подзаголовок с оранжевой чертой (H3)', 'h2' => 'Ещё один заголовок секции (H2)'])->default('h3'),
                    ]),
                Block::make('rule')->label('Разделитель')->icon('heroicon-o-minus')->schema([]),
                Block::make('image')
                    ->label($label('Картинка', 'alt'))
                    ->icon('heroicon-o-photo')
                    ->schema([
                        $image('image', 'Картинка', 'Широкий баннер, примерно 1180×202.')->required(),
                        TextInput::make('alt')->label('Подпись для поисковиков (alt)')->maxLength(160),
                        Radio::make('style')->label('Вид')->options(['banner' => 'Баннер в тексте', 'promo' => 'Промо-баннер в конце секции'])->default('banner')->inline(),
                    ]),
                Block::make('cards')
                    ->label($label('Карточки'))
                    ->icon('heroicon-o-squares-2x2')
                    ->schema([
                        Radio::make('columns')->label('Карточек в ряд')->options([2 => '2', 3 => '3'])->default(2)->inline(),
                        Radio::make('style')->label('Вид карточек')->options(['dark' => 'Тёмные', 'outline' => 'С рамкой, оранжевый заголовок'])->default('dark')->inline(),
                        Repeater::make('cards')
                            ->label('Карточки')
                            ->schema([
                                TextInput::make('title')->label('Заголовок карточки')->required()->maxLength(160),
                                Textarea::make('text')->label('Текст')->rows(3)->maxLength(3000)->helperText($markup),
                                Textarea::make('items')->label('Или список')->rows(4)->maxLength(3000)->helperText($lines),
                            ])
                            ->defaultItems(2)
                            ->collapsible()
                            ->reorderable()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                            ->addActionLabel('Добавить карточку'),
                    ]),
                Block::make('checks')
                    ->label('Список с галочками')
                    ->icon('heroicon-o-check-circle')
                    ->schema([
                        Textarea::make('items')->label('Пункты')->rows(5)->required()->maxLength(5000)->helperText($lines),
                        Toggle::make('compact')->label('Плотный список с отступом сверху (как «How We Stand Out»)'),
                    ]),
                Block::make('steps')
                    ->label('Нумерованные шаги')
                    ->icon('heroicon-o-list-bullet')
                    ->schema([
                        Textarea::make('items')->label('Шаги')->rows(5)->required()->maxLength(3000)->helperText('Каждый шаг с новой строки, номера проставятся сами.'),
                    ]),
                Block::make('tip')
                    ->label($label('Подсказка «!»'))
                    ->icon('heroicon-o-exclamation-circle')
                    ->schema([
                        TextInput::make('title')->label('Заголовок')->required()->maxLength(160),
                        Textarea::make('text')->label('Текст')->rows(2)->maxLength(1000)->helperText($markup),
                    ]),
                Block::make('feature')
                    ->label($label('Статья (широкая плашка)'))
                    ->icon('heroicon-o-newspaper')
                    ->schema([
                        $image('image', 'Картинка', '360×180.'),
                        TextInput::make('title')->label('Заголовок')->required()->maxLength(200),
                        TextInput::make('meta')->label('Подпись под заголовком')->maxLength(80)->helperText('Например дата: 10 September 2024.'),
                        TextInput::make('url')->label('Ссылка')->maxLength(500)->placeholder('/content/guides/...'),
                    ]),
                Block::make('articles')
                    ->label('Статьи (список с кнопкой)')
                    ->icon('heroicon-o-queue-list')
                    ->schema([
                        Repeater::make('items')
                            ->label('Статьи')
                            ->schema([
                                $image('image', 'Картинка', '400×200.'),
                                TextInput::make('label')->label('Метка на картинке')->maxLength(40)->placeholder('Guide'),
                                TextInput::make('title')->label('Заголовок')->required()->maxLength(200),
                                TextInput::make('meta')->label('Подпись под заголовком')->maxLength(80),
                                TextInput::make('url')->label('Ссылка')->maxLength(500),
                                TextInput::make('button')->label('Текст кнопки')->maxLength(40)->placeholder('Read'),
                            ])
                            ->defaultItems(2)
                            ->collapsible()
                            ->reorderable()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                            ->addActionLabel('Добавить статью'),
                    ]),
                Block::make('guides')
                    ->label($label('Группа гайдов', 'heading'))
                    ->icon('heroicon-o-book-open')
                    ->schema([
                        TextInput::make('heading')->label('Заголовок группы')->required()->maxLength(200)->helperText('Номер 01, 02… проставится сам по порядку групп в секции.'),
                        Textarea::make('text')->label('Текст')->rows(2)->maxLength(2000),
                        Radio::make('columns')->label('Карточек в ряд')->options([3 => '3', 2 => '2'])->default(3)->inline(),
                        Repeater::make('cards')
                            ->label('Карточки гайдов')
                            ->schema([
                                $image('image', 'Картинка', '260×180.'),
                                TextInput::make('title')->label('Заголовок')->required()->maxLength(200),
                                TextInput::make('url')->label('Ссылка')->maxLength(500),
                            ])
                            ->defaultItems(3)
                            ->collapsible()
                            ->reorderable()
                            ->grid(3)
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                            ->addActionLabel('Добавить гайд'),
                    ]),
                Block::make('guides_link')
                    ->label($label('Кнопка «Все гайды»', 'label'))
                    ->icon('heroicon-o-arrow-right')
                    ->schema([
                        TextInput::make('label')->label('Текст')->required()->maxLength(80)->placeholder('Read All Guides (59)'),
                        TextInput::make('url')->label('Ссылка')->maxLength(500)->placeholder('/content/guides/'),
                    ]),
            ])
            ->blockNumbers(false)
            ->blockPickerColumns(3)
            ->collapsible()
            ->collapsed()
            ->cloneable()
            ->reorderableWithButtons()
            ->addActionLabel('Добавить элемент');
    }

    private static function teamSectionsField(): Repeater
    {
        $languages = ['en' => 'Английский', 'de' => 'Немецкий', 'fr' => 'Французский'];
        $localeTabs = [];
        foreach ($languages as $locale => $tab) {
            $localeTabs[] = Tab::make($tab)->schema([
                TextInput::make("title.{$locale}")
                    ->label('Заголовок группы')
                    ->required($locale === 'en')
                    ->maxLength(120)
                    ->placeholder($locale === 'en' ? 'Our Editors' : 'Пусто — английский'),
                Textarea::make("text.{$locale}")
                    ->label('Текст под заголовком')
                    ->rows(3)
                    ->maxLength(2000)
                    ->placeholder($locale === 'en' ? 'Необязательно' : 'Пусто — английский'),
            ]);
        }

        return Repeater::make('sections')
            ->label('Группы на странице /authors/')
            ->helperText('Порядок групп на сайте — как здесь, перетаскивайте за ручку. Автор, не выбранный ни в одной группе, на странице команды не показывается (его личная страница работает). Пустая группа без карточки «?» скрывается.')
            ->schema([
                Tabs::make('Языки')->tabs($localeTabs),
                Select::make('author_ids')
                    ->label('Авторы в группе')
                    ->multiple()
                    ->searchable()
                    ->options(fn (): array => Author::query()
                        ->orderBy('sort_order')
                        ->orderBy('id')
                        ->get(['id', 'name', 'slug', 'is_published'])
                        ->mapWithKeys(fn (Author $author): array => [
                            $author->id => $author->displayName('en').($author->is_published ? '' : ' (не опубликован — на сайте не виден)'),
                        ])
                        ->all())
                    ->helperText('Карточки идут в порядке выбора. Чтобы переставить — удалите автора и выберите заново. Если автор выбран в двух группах, он покажется только в первой.'),
                Toggle::make('show_join')
                    ->label('Карточка «?» (become part of the team) в конце группы')
                    ->helperText('Подпись и ссылка карточки — на вкладке «Английский» и других языков, в «Тексты страницы».'),
            ])
            ->defaultItems(0)
            ->reorderableWithDragAndDrop()
            ->collapsible()
            ->cloneable()
            ->itemLabel(function (array $state): ?string {
                $count = count(array_filter((array) ($state['author_ids'] ?? [])));

                return trim(($state['title']['en'] ?? '') ?: 'Новая группа').' · авторов: '.$count;
            })
            ->addActionLabel('Добавить группу');
    }

    /**
     * @return array<int, TextInput|Textarea>
     */
    private static function textFields(?PageSetting $record, string $locale): array
    {
        $fields = [];
        foreach ($record?->textFields() ?? [] as $field => $definition) {
            $placeholder = $definition['default'] !== '' ? (string) __($definition['default'], [], $locale) : null;
            $input = ($definition['multiline'] ?? false)
                ? Textarea::make("texts.{$locale}.{$field}")->rows(4)->maxLength(2000)
                : TextInput::make("texts.{$locale}.{$field}")->maxLength(255);

            $fields[] = $input
                ->label($definition['label'])
                ->placeholder($placeholder)
                ->helperText($definition['help'] ?? null);
        }

        return $fields;
    }
}
