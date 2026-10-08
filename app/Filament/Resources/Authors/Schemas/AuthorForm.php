<?php

namespace App\Filament\Resources\Authors\Schemas;

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
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Автор')
                ->persistTabInQueryString()
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Основное')->schema([
                        Section::make('Профиль')
                            ->columns(2)
                            ->schema([
                                TextInput::make('slug')
                                    ->label('Адрес (slug)')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->alphaDash()
                                    ->helperText('Страница автора: /authors/{slug}/. Если пусто при создании — из английского имени.'),
                                TextInput::make('role')
                                    ->label('Должность')
                                    ->required()
                                    ->default('Slot Analyst')
                                    ->maxLength(80)
                                    ->helperText('Под именем на странице автора (Project Manager) и в блоке Editorial pledge у слота.'),
                                DatePicker::make('started_at')
                                    ->label('В команде с')
                                    ->helperText('Дата под должностью с подписью Joined. Пусто — строки не будет.'),
                                FilamentR2::prepare(
                                    FileUpload::make('avatar_path')
                                        ->label('Фото')
                                        ->directory('authors')
                                        ->image()
                                        ->imageEditor()
                                        ->maxSize(5120)
                                        ->helperText('Квадратное фото, от 508×508. На сайте — круг на оранжевой карточке, в публикациях — аватар.')
                                ),
                            ]),
                        Section::make('Публикация')
                            ->columns(2)
                            ->schema([
                                Toggle::make('is_published')
                                    ->label('Опубликован')
                                    ->default(false)
                                    ->helperText('Страница /authors/{slug}/ открывается только у опубликованных авторов.'),
                                TextInput::make('sort_order')
                                    ->label('Порядок')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Для списка команды: меньше — выше.'),
                            ]),
                    ]),
                    Tab::make('Английский')->schema(self::localeFields('en', 'английский', true)),
                    Tab::make('Немецкий')->schema(self::localeFields('de', 'немецкий', false)),
                    Tab::make('Французский')->schema(self::localeFields('fr', 'французский', false)),
                    Tab::make('Избранное')->schema([
                        Section::make('Favorite Slots & Red Flags Slots')
                            ->description('Блок под биографией. Пустые списки на сайте не показываются; если пусто всё — блока не будет. Порядок — как выбран здесь. Блоки «Latest Slots» (до 12 слотов, где он автор) и «Latest Posts» (до 6 его публикаций) собираются сами.')
                            ->schema([
                                self::slotPicker('favorite_slot_ids', 'My Favorite Slots')
                                    ->helperText('Любимые слоты автора — ссылки на их страницы.'),
                                self::slotPicker('red_flag_slot_ids', 'Red Flags Slots')
                                    ->helperText('Слоты, к которым у автора есть претензии.'),
                                Repeater::make('top_streamers')
                                    ->label('My Top Streamers')
                                    ->schema([
                                        TextInput::make('name')->label('Имя стримера')->required()->maxLength(80),
                                        TextInput::make('url')->label('Ссылка')->url()->maxLength(255)
                                            ->helperText('Необязательно: канал или страница стримера. Без ссылки — просто имя.'),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(0)
                                    ->collapsible()
                                    ->reorderable()
                                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                    ->addActionLabel('Добавить стримера'),
                                Select::make('favorite_post_ids')
                                    ->label('Favorite Posts')
                                    ->multiple()
                                    ->searchable()
                                    ->options(fn (): array => Post::query()
                                        ->latest('created_at')
                                        ->get()
                                        ->mapWithKeys(fn (Post $post): array => [$post->id => $post->displayTitle('en').($post->is_published ? '' : ' (черновик)')])
                                        ->all())
                                    ->helperText('Любые публикации из раздела Контент. Черновики на сайте не показываются.'),
                            ]),
                    ]),
                ]),
        ]);
    }

    /**
     * @return list<Component>
     */
    private static function localeFields(string $locale, string $label, bool $required): array
    {
        $fallback = $locale === 'en'
            ? 'Пустые поля заменяются стандартными значениями.'
            : 'Пустые поля берутся из английского варианта.';

        return [
            Section::make("Страница автора ({$label})")
                ->description($fallback)
                ->schema([
                    TextInput::make("name.{$locale}")
                        ->label("Имя ({$label})")
                        ->required($required)
                        ->maxLength(120)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, Set $set, Get $get) use ($locale): void {
                            if ($locale !== 'en' || filled($get('slug'))) {
                                return;
                            }
                            $set('slug', Str::slug((string) $state));
                        })
                        ->helperText('Крупно на карточке, в подписи публикаций и слотов.'),
                    TextInput::make("page_title.{$locale}")
                        ->label('Заголовок страницы (H1)')
                        ->maxLength(160)
                        ->placeholder('Dmitriy Collins – About Dmitriy and His Position')
                        ->helperText('H1 и последняя крошка. Пусто — «{Имя} – About the Author».'),
                    TagsInput::make("traits.{$locale}")
                        ->label('Теги')
                        ->placeholder('Project Visionary')
                        ->reorderable()
                        ->helperText('Плашки под датой: Project Visionary, Slots & Casino Reviewer… Enter — добавить тег.'),
                    RichEditor::make("bio.{$locale}")
                        ->label("Биография ({$label})")
                        ->toolbarButtons([
                            ['bold', 'italic', 'link'],
                            ['bulletList', 'orderedList'],
                            ['undo', 'redo'],
                        ])
                        ->helperText('Абзацы под карточкой. Ссылки можно ставить на страницы сайта.'),
                ]),
            Section::make("SEO ({$label})")
                ->description($fallback)
                ->schema([
                    TextInput::make("meta_title.{$locale}")
                        ->label('Meta title')
                        ->maxLength(120)
                        ->helperText('Пусто — «H1 | slots.tube». Оптимально 50–60 символов.'),
                    Textarea::make("meta_description.{$locale}")
                        ->label('Meta description')
                        ->rows(3)
                        ->maxLength(300)
                        ->helperText('Пусто — начало биографии. Оптимально 140–160 символов.'),
                ]),
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
                ->mapWithKeys(fn (Slot $slot): array => [$slot->id => $slot->displayTitle('en').($slot->is_published ? '' : ' (черновик)')])
                ->all());
    }
}
