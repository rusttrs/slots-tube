<?php

namespace App\Filament\Resources\Authors\Schemas;

use App\Models\Author;
use App\Models\Post;
use App\Models\Slot;
use App\Support\FilamentR2;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class AuthorForm
{
    private const LOCALES = [
        'en' => ['Английский', 'английский'],
        'de' => ['Немецкий', 'немецкий'],
        'fr' => ['Французский', 'французский'],
    ];

    public static function configure(Schema $schema): Schema
    {
        $localeTabs = [];
        foreach (self::LOCALES as $locale => [$tab, $label]) {
            $localeTabs[] = Tab::make($tab)->schema(self::localeFields($locale, $label));
        }

        return $schema->components([
            Tabs::make('Автор')
                ->persistTabInQueryString()
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Профиль')->schema(self::profileFields()),
                    ...$localeTabs,
                    Tab::make('Избранное')->schema(self::favoritesFields()),
                    Tab::make('Слоты и публикации')->schema(self::latestFields()),
                    Tab::make('SEO')->schema(self::seoFields()),
                ]),
        ]);
    }

    /**
     * @return list<Component>
     */
    private static function profileFields(): array
    {
        return [
            Section::make('Карточка автора')
                ->description('Левая оранжевая карточка и строка Joined. Имя, должность, теги и биография — на вкладках языков.')
                ->columns(2)
                ->schema([
                    FilamentR2::prepare(
                        FileUpload::make('avatar_path')
                            ->label('Фото (аватар)')
                            ->directory('authors')
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120)
                            ->helperText('Квадрат от 508×508, лицо по центру. На странице — круг 254 px на оранжевой карточке; ещё аватар в публикациях и og:image.')
                    ),
                    DatePicker::make('started_at')
                        ->label('Joined — в команде с')
                        ->native(false)
                        ->displayFormat('d.m.Y')
                        ->helperText('Дата под должностью, на сайте: November 15, 2021. Пусто — строки Joined не будет.'),
                ]),
            Section::make('Адрес и публикация')
                ->columns(2)
                ->schema([
                    TextInput::make('slug')
                        ->label('Адрес (slug)')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->alphaDash()
                        ->maxLength(80)
                        ->helperText('Страница: /authors/{slug}/. Подставляется из английского имени, если пусто.'),
                    TextInput::make('sort_order')
                        ->label('Порядок')
                        ->numeric()
                        ->default(0)
                        ->helperText('Для списка команды: меньше — выше.'),
                    Toggle::make('is_published')
                        ->label('Опубликован')
                        ->default(false)
                        ->helperText('Только у опубликованных авторов открывается страница и имя становится ссылкой в публикациях и слотах.'),
                ]),
        ];
    }

    /**
     * @return list<Component>
     */
    private static function localeFields(string $locale, string $label): array
    {
        $isEn = $locale === 'en';
        $fallback = $isEn
            ? 'Пустые поля заменяются стандартными значениями.'
            : 'Пустые поля берутся из английского варианта.';

        return [
            Section::make("Профиль ({$label})")
                ->description($fallback)
                ->columns(2)
                ->schema([
                    TextInput::make("name.{$locale}")
                        ->label('Имя')
                        ->required($isEn)
                        ->maxLength(120)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, Set $set, Get $get) use ($isEn): void {
                            if (! $isEn || filled($get('slug'))) {
                                return;
                            }
                            $set('slug', Str::slug((string) $state));
                        })
                        ->helperText('Крупно рядом с фото; подпись в публикациях и у слотов.'),
                    TextInput::make("position.{$locale}")
                        ->label('Должность')
                        ->required($isEn)
                        ->maxLength(80)
                        ->placeholder('Project Manager')
                        ->helperText('Серым под именем. Ещё — в блоке Editorial pledge у слота: «{должность} at SlotsTube».'),
                    TextInput::make("page_title.{$locale}")
                        ->label('Заголовок страницы (H1)')
                        ->maxLength(160)
                        ->placeholder('Dmitriy Collins – About Dmitriy and His Position')
                        ->columnSpanFull()
                        ->helperText('Большой заголовок и последняя хлебная крошка. Пусто — «{Имя} – About the Author».'),
                    TagsInput::make("traits.{$locale}")
                        ->label('Теги: специализации и увлечения')
                        ->placeholder('Project Visionary')
                        ->reorderable()
                        ->columnSpanFull()
                        ->helperText('Тёмные плашки с «7» под Joined: Project Visionary, Slots & Casino Reviewer, Scam Investigator… Enter — добавить. Удобно 3–6 штук.'),
                    RichEditor::make("bio.{$locale}")
                        ->label('Описание (биография)')
                        ->columnSpanFull()
                        ->toolbarButtons([
                            ['bold', 'italic', 'link'],
                            ['bulletList', 'orderedList'],
                            ['undo', 'redo'],
                        ])
                        ->helperText('Абзацы под карточкой на всю ширину. Ссылки можно ставить на страницы сайта. Начало текста идёт в meta description, если оно пустое.'),
                ]),
            Section::make("Заголовки блоков ({$label})")
                ->description('Необязательно: по умолчанию стоят тексты из макета.')
                ->columns(3)
                ->collapsed()
                ->schema([
                    TextInput::make("favorites_title.{$locale}")
                        ->label('Блок избранного')
                        ->maxLength(120)
                        ->placeholder(__('author.favorites_title', [], $locale)),
                    TextInput::make("latest_slots_title.{$locale}")
                        ->label('Блок слотов')
                        ->maxLength(120)
                        ->placeholder(__('author.latest_slots', ['name' => '{Имя}'], $locale)),
                    TextInput::make("latest_posts_title.{$locale}")
                        ->label('Блок публикаций')
                        ->maxLength(120)
                        ->placeholder(__('author.latest_posts', ['name' => '{Имя}'], $locale)),
                ]),
        ];
    }

    /**
     * @return list<Component>
     */
    private static function favoritesFields(): array
    {
        return [
            Section::make('Favorite Slots & Red Flags Slots')
                ->description('Блок под биографией. Пустой пункт не показывается; если пусто всё — блока нет. Порядок на сайте — как выбран здесь.')
                ->schema([
                    self::slotPicker('favorite_slot_ids', 'My Favorite Slots — любимые слоты')
                        ->helperText('Ссылки через запятую на страницы слотов.'),
                    self::slotPicker('red_flag_slot_ids', 'Red Flags Slots — слоты с претензиями')
                        ->helperText('Слоты, к которым у автора есть вопросы: честность, RTP, условия.'),
                    Repeater::make('top_streamers')
                        ->label('My Top Streamers — любимые стримеры')
                        ->schema([
                            TextInput::make('name')->label('Имя стримера')->required()->maxLength(80),
                            TextInput::make('url')->label('Ссылка')->url()->maxLength(255)
                                ->helperText('Канал или страница на сайте. Без ссылки — просто имя.'),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->collapsible()
                        ->reorderable()
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                        ->addActionLabel('Добавить стримера'),
                    self::postPicker('favorite_post_ids', 'Favorite Posts — любимые публикации')
                        ->helperText('Список со звёздочками. Любые публикации из раздела Контент.'),
                ]),
        ];
    }

    /**
     * @return list<Component>
     */
    private static function latestFields(): array
    {
        return [
            Section::make('Latest Slots')
                ->description('Сетка обложек слотов с кнопкой Explore All Slots.')
                ->schema([
                    Toggle::make('show_latest_slots')
                        ->label('Показывать блок')
                        ->default(true)
                        ->live(),
                    self::slotPicker('latest_slot_ids', 'Слоты вручную')
                        ->maxItems(Author::LATEST_SLOTS)
                        ->visible(fn (Get $get): bool => (bool) $get('show_latest_slots'))
                        ->helperText('Пусто — автоматически до '.Author::LATEST_SLOTS.' последних опубликованных слотов, где он автор. Если выбрать — только эти, в этом порядке.'),
                ]),
            Section::make('Latest Posts')
                ->description('Карточки публикаций с кнопкой Explore All News.')
                ->schema([
                    Toggle::make('show_latest_posts')
                        ->label('Показывать блок')
                        ->default(true)
                        ->live(),
                    self::postPicker('latest_post_ids', 'Публикации вручную')
                        ->maxItems(Author::LATEST_POSTS)
                        ->visible(fn (Get $get): bool => (bool) $get('show_latest_posts'))
                        ->helperText('Пусто — автоматически до '.Author::LATEST_POSTS.' последних публикаций автора. Если выбрать — только эти, в этом порядке.'),
                ]),
        ];
    }

    /**
     * @return list<Component>
     */
    private static function seoFields(): array
    {
        $meta = [];
        foreach (self::LOCALES as $locale => [, $label]) {
            $meta[] = Section::make("Meta ({$label})")
                ->description($locale === 'en'
                    ? 'Пусто — title из H1, description из начала биографии.'
                    : 'Пусто — английский вариант, а без него стандартный.')
                ->collapsible()
                ->schema([
                    TextInput::make("meta_title.{$locale}")
                        ->label('Meta title')
                        ->maxLength(120)
                        ->helperText('Пусто — «H1 | slots.tube». Оптимально 50–60 символов.'),
                    Textarea::make("meta_description.{$locale}")
                        ->label('Meta description')
                        ->rows(3)
                        ->maxLength(300)
                        ->helperText('Оптимально 140–160 символов.'),
                ]);
        }

        return [
            Section::make('Индексация и профили')
                ->schema([
                    Toggle::make('noindex')
                        ->label('Закрыть страницу от индексации (noindex)')
                        ->default(false)
                        ->helperText('Страница открывается, но поисковикам отдаётся noindex, follow.'),
                    Repeater::make('social_links')
                        ->label('Профили автора в сети')
                        ->schema([
                            Select::make('network')
                                ->label('Сеть')
                                ->options(Author::SOCIAL_NETWORKS)
                                ->required()
                                ->native(false),
                            TextInput::make('url')
                                ->label('Ссылка')
                                ->url()
                                ->required()
                                ->maxLength(255),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->reorderable()
                        ->itemLabel(fn (array $state): ?string => Author::SOCIAL_NETWORKS[$state['network'] ?? ''] ?? null)
                        ->addActionLabel('Добавить профиль')
                        ->helperText('На странице не показываются: идут в разметку schema.org (sameAs), чтобы поисковики связали автора с его профилями.'),
                ]),
            ...$meta,
        ];
    }

    private static function slotPicker(string $name, string $label): Select
    {
        return Select::make($name)
            ->label($label)
            ->multiple()
            ->searchable()
            ->options(fn (): array => Slot::query()
                ->orderBy('slug')
                ->get(['id', 'slug', 'title', 'is_published'])
                ->mapWithKeys(fn (Slot $slot): array => [$slot->id => $slot->displayTitle('en').($slot->is_published ? '' : ' (черновик — на сайте не виден)')])
                ->all());
    }

    private static function postPicker(string $name, string $label): Select
    {
        return Select::make($name)
            ->label($label)
            ->multiple()
            ->searchable()
            ->options(fn (): array => Post::query()
                ->latest('created_at')
                ->get(['id', 'slug', 'title', 'type', 'is_published'])
                ->mapWithKeys(fn (Post $post): array => [
                    $post->id => '['.(Post::typeOptions()[$post->type] ?? $post->type).'] '.$post->displayTitle('en').($post->is_published ? '' : ' (черновик — на сайте не виден)'),
                ])
                ->all());
    }
}
