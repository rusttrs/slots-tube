<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Filament\RichContent\PostFigureBlock;
use App\Models\Author;
use App\Models\Post;
use App\Support\FilamentR2;
use App\Support\MediaMirror;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Публикация')
                ->persistTabInQueryString()
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Основное')->schema([
                        Section::make('Раздел и адрес')
                            ->columns(2)
                            ->schema([
                                Select::make('type')
                                    ->label('Раздел')
                                    ->options(Post::typeOptions())
                                    ->required()
                                    ->native(false)
                                    ->helperText('News → /content/news/, Blogs → /content/blogs/, Guides → /content/guides/, Streamers → /content/streamers/.'),
                                TextInput::make('slug')
                                    ->label('Адрес (slug)')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->rules([Rule::notIn(Post::reservedSlugs())])
                                    ->helperText('Страница материала: /content/{slug}/. Нельзя: news, blogs, guides, streamers. Если пусто при создании — из английского заголовка.'),
                            ]),
                        Section::make('Обложка')
                            ->schema([
                                FilamentR2::prepare(
                                    FileUpload::make('cover_path')
                                        ->label('Обложка (hero)')
                                        ->directory('posts')
                                        ->image()
                                        ->imageEditor()
                                        ->maxSize(5120)
                                        ->helperText('Широкая картинка сверху страницы. Лучше горизонтальный кадр, около 1480×520.')
                                ),
                            ]),
                        Section::make('Публикация')
                            ->columns(2)
                            ->schema([
                                Toggle::make('is_published')
                                    ->label('Опубликована')
                                    ->default(false)
                                    ->helperText('На сайте видны только опубликованные материалы: /content/{slug}/.'),
                                Toggle::make('is_featured')
                                    ->label('Избранное')
                                    ->default(false)
                                    ->helperText('Для будущих листингов. На странице материала сейчас не влияет.'),
                                Select::make('author_id')
                                    ->label('Автор')
                                    ->relationship('author', 'slug')
                                    ->getOptionLabelFromRecordUsing(fn (Author $record): string => $record->displayName('en'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->validationMessages(['required' => 'Выберите автора: материал публикуется от его имени.'])
                                    ->helperText('Обязательно. От его имени публикуется материал: аватар и имя под заголовком. Список — раздел Контент → Авторы.'),
                                DateTimePicker::make('created_at')
                                    ->label('Дата создания')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->seconds(false)
                                    ->helperText('Ставится сама. На сайте в byline — эта дата (как Published у слота).')
                                    ->visible(fn (?Post $record): bool => (bool) $record?->exists),
                                DatePicker::make('content_updated_on')
                                    ->label('Дата обновления на сайте')
                                    ->live()
                                    ->helperText('Необязательно. Если заполнить, под автором появится «Updated — дата» и нужно выбрать, кто обновил.'),
                                Select::make('updated_by_author_id')
                                    ->label('Кто обновил')
                                    ->relationship('updatedByAuthor', 'slug')
                                    ->getOptionLabelFromRecordUsing(fn (Author $record): string => $record->displayName('en'))
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn (Get $get): bool => filled($get('content_updated_on')))
                                    ->required(fn (Get $get): bool => filled($get('content_updated_on')))
                                    ->validationMessages(['required' => 'Укажите автора обновления: дата обновления заполнена.'])
                                    ->helperText('Обязательно, если есть дата обновления. Выбирается из раздела Контент → Авторы.'),
                            ]),
                    ]),
                    Tab::make('Английский')->schema(self::localeFields('en', 'английский', true)),
                    Tab::make('Немецкий')->schema(self::localeFields('de', 'немецкий', false)),
                    Tab::make('Французский')->schema(self::localeFields('fr', 'французский', false)),
                ]),
        ]);
    }

    /**
     * @return list<\Filament\Forms\Components\Component>
     */
    private static function localeFields(string $locale, string $label, bool $required): array
    {
        return [
            Section::make("Контент ({$label})")
                ->schema([
                    TextInput::make("title.{$locale}")
                        ->label("Заголовок ({$label})")
                        ->required($required)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, Set $set, Get $get) use ($locale): void {
                            if ($locale !== 'en' || filled($get('slug'))) {
                                return;
                            }
                            $set('slug', Str::slug((string) $state));
                        })
                        ->helperText($locale === 'en'
                            ? 'H1 на сайте. Из него собирается адрес /content/{slug}/, если slug ещё пустой.'
                            : 'Заголовок на этом языке.'),
                    Textarea::make("excerpt.{$locale}")
                        ->label("Анонс ({$label})")
                        ->rows(3)
                        ->helperText('Короткий текст для SEO и карточек в листинге. На странице материала не показывается.'),
                    self::bodyEditor("body.{$locale}", $label),
                ]),
        ];
    }

    private static function bodyEditor(string $name, string $label): RichEditor
    {
        return RichEditor::make($name)
            ->label("Текст ({$label})")
            ->columnSpanFull()
            ->fileAttachmentsDisk('r2')
            ->fileAttachmentsDirectory('posts/body')
            ->fileAttachmentsVisibility('private')
            ->getFileAttachmentUrlUsing(function (string $file): string {
                MediaMirror::mirrorPath($file);

                return media_url($file);
            })
            ->customBlocks([PostFigureBlock::class])
            ->toolbarButtons([
                ['bold', 'italic', 'link'],
                ['h2', 'h3'],
                ['blockquote', 'bulletList', 'orderedList'],
                ['attachFiles', 'customBlocks', 'undo', 'redo'],
            ])
            ->helperText('Как у слота: абзацы, H2/H3, списки. Картинка — кнопка файла (Alt = подпись) или блок «Картинка с подписью».');
    }
}
