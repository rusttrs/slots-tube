<?php

namespace App\Filament\Resources\Slots\Schemas;

use App\Models\Author;
use App\Models\Provider;
use App\Models\Slot;
use App\Support\FilamentR2;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
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

class SlotForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Slot')
                ->persistTabInQueryString()
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Основное')->schema(self::basicsFields()),
                    Tab::make('Об игре')->schema(self::gameInfoFields()),
                    Tab::make('Английский')->schema(self::localeContentFields('en', 'английский')),
                    Tab::make('Немецкий')->schema(self::localeContentFields('de', 'немецкий')),
                    Tab::make('Французский')->schema(self::localeContentFields('fr', 'французский')),
                    Tab::make('Символы')->schema(self::symbolsMediaFields()),
                    Tab::make('Скриншоты')->schema(self::mediaFields()),
                ]),
        ]);
    }

    protected static function yesNoOptions(): array
    {
        return [
            'Yes' => 'Да',
            'No' => 'Нет',
            'Market dependent' => 'Зависит от рынка',
            'No standard Wild' => 'Нет обычного Wild',
        ];
    }

    protected static function basicsFields(): array
    {
        return [
            Section::make('Карточка слота')
                ->description('Название, адрес, провайдер, обложка и демо. Оценка игроков сюда не вводится: её ставят только вошедшие пользователи, по одной на слот.')
                ->columns(2)
                ->schema([
                    TextInput::make('title.en')
                        ->label('Название слота (английский)')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state, Set $set, Get $get, ?Slot $record): void {
                            if (blank($state)) {
                                return;
                            }
                            // Autofill slug on create, or when slug is still empty.
                            if ($record?->exists && filled($get('slug'))) {
                                return;
                            }
                            $set('slug', Slot::makeSlugFromTitle($state));
                        })
                        ->helperText('На сайте заголовок будет: «{название} Demo & Slot Review».'),
                    TextInput::make('slug')
                        ->label('Адрес страницы (slug)')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText('Адрес: /slots/{slug}/. Создаётся из английского названия, можно поправить.'),
                    Select::make('provider_id')
                        ->label('Провайдер')
                        ->relationship('provider', 'slug')
                        ->getOptionLabelFromRecordUsing(fn (Provider $record): string => $record->displayName('en'))
                        ->searchable()
                        ->preload()
                        ->helperText('Показывается в подзаголовке, таблице об игре и блоке провайдера.'),
                    Select::make('author_id')
                        ->label('Автор')
                        ->relationship('author', 'slug')
                        ->getOptionLabelFromRecordUsing(fn (Author $record): string => $record->displayName('en'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->validationMessages(['required' => 'Выберите автора: слот публикуется от его имени.'])
                        ->helperText('Обязательно. От его имени публикуется слот: «Published … by», блок Editorial pledge. Список — раздел Контент → Авторы.'),
                    Select::make('reviewer_id')
                        ->label('Редактор (Reviewed by)')
                        ->relationship('reviewer', 'slug')
                        ->getOptionLabelFromRecordUsing(fn (Author $record): string => $record->displayName('en'))
                        ->searchable()
                        ->preload()
                        ->helperText('Необязательно. Карточка «Reviewed by» в блоке Editorial pledge; если пусто — карточки нет.'),
                    DatePicker::make('last_reviewed_on')
                        ->label('Last reviewed')
                        ->helperText('Дата в карточке «Last reviewed». Если пусто — карточка не показывается.'),
                    TextInput::make('review_focus')
                        ->label('Review focus')
                        ->placeholder('Rules, RTP and operator checks')
                        ->helperText('Текст карточки «Review focus». Если пусто — карточка не показывается.'),
                    FilamentR2::prepare(
                        FileUpload::make('cover_path')
                            ->label('Скрин игры')
                            ->directory('slots/covers')
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120)
                            ->helperText('Фон блока Play For Free и картинка в списках. Лучше горизонтальный скриншот, около 1200×630.')
                            ->columnSpanFull()
                    ),
                    FilamentR2::prepare(
                        FileUpload::make('about_cover_path')
                            ->label('Лого слота')
                            ->directory('slots/covers')
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120)
                            ->helperText('Карточка под снапшотом (постер/лого). Если пусто, берётся скрин игры выше. Лучше вертикаль около 290×378.')
                            ->columnSpanFull()
                    ),
                    TextInput::make('demo_url')
                        ->label('Ссылка на демо')
                        ->url()
                        ->helperText('Открывается в окне на странице по кнопке Play For Free. Карточки Where to play — блок «Бонусы казино на этой странице» внизу формы слота.')
                        ->columnSpanFull(),
                    TextInput::make('editorial_score')
                        ->label('Наша оценка')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(5)
                        ->step(0.1)
                        ->helperText('Наша оценка у обложки, не рейтинг игроков. Шкала до 5: на сайте будет «4.8 out of 5».'),
                    Toggle::make('show_session_data')
                        ->label('Показать блок Observed Session Data')
                        ->helperText('Редактор ничего не пишет в этот блок. Включает полоску сверху и секцию ниже. Цифры, таблицы и паспорт данных пока статичные (1h / 24h / 7d в JS). Из слота подставляются только название в абзац и дефолтный RTP в строку Build / RTP config — как эталон «тот ли билд». RTP в таблице Coverage by casino не из Game information. Фильтры казино и режима в вёрстке, на данные не влияют.'),
                ]),
            Section::make('Публикация')
                ->columns(2)
                ->schema([
                    Toggle::make('is_published')
                        ->label('Опубликован')
                        ->helperText('На сайте видны только опубликованные слоты.'),
                    DateTimePicker::make('created_at')
                        ->label('Дата создания')
                        ->disabled()
                        ->dehydrated(false)
                        ->seconds(false)
                        ->helperText('Ставится сама. На сайте в Published — эта дата.')
                        ->visible(fn (?Slot $record): bool => (bool) $record?->exists),
                    DatePicker::make('content_updated_on')
                        ->label('Дата обновления на сайте')
                        ->live()
                        ->helperText('Необязательно. Если заполнить, справа появится «Updated — дата» и нужно выбрать, кто обновил.'),
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
        ];
    }

    protected static function gameInfoFields(): array
    {
        $hint = 'Показывается в таблице «Об игре». Позже пойдёт в фильтры Free Slots.';

        return [
            Section::make('Таблица об игре')
                ->description('Строки таблицы «{название} game information». Пустые поля на сайте не показываются. Таблица свёрнута, кнопка Show all game details не редактируется. Yes / No / Market dependent в базе остаются английскими.')
                ->columns(2)
                ->schema([
                    DatePicker::make('release_date')->label('Дата выхода')->helperText('На сайте: Month Year, например February 2021.'),
                    TextInput::make('game_type')->label('Тип игры')->placeholder('Video Slot')->helperText($hint),
                    TextInput::make('grid')->label('Сетка')->placeholder('6×5')->helperText($hint),
                    TextInput::make('win_system')->label('Система выигрыша')->placeholder('Pay Anywhere (8+ matches)')->helperText($hint),
                    TextInput::make('rtp')
                        ->label('RTP, число')
                        ->numeric()
                        ->helperText('Число для подзаголовка под H1 и строки Default RTP. На сайте будет как 96.50%.'),
                    TextInput::make('rtp_min')
                        ->label('RTP от')
                        ->numeric()
                        ->helperText('Нижняя граница в заголовке «{название} Slot Return: from … to …». Два знака после запятой. Слово POP из макета не пишем.'),
                    TextInput::make('rtp_max')
                        ->label('RTP до')
                        ->numeric()
                        ->helperText('Верхняя граница в том же заголовке. Если одно из полей пустое, подставится число из «RTP, число».'),
                    TextInput::make('rtp_text')
                        ->label('RTP текстом в таблице')
                        ->placeholder('96.50% default; verify in game')
                        ->helperText('Строка RTP только в таблице Game information. Если пусто — число из «RTP, число» как 96.50%. Колонка похожих слотов берёт «RTP от/до» или «RTP, число», не это поле.')
                        ->columnSpanFull(),
                    TextInput::make('max_win')->label('Максимальный выигрыш')->placeholder('x5000')->helperText('Как ввели: таблица Game information, карточка max win в блоке RTP и колонка Similar slots. Только авто-подзаголовок под H1 нормализует число в 5,000×. Если заполнить поле «Текст под H1», подзаголовок тоже идёт как ввели.'),
                    Select::make('volatility')
                        ->label('Волатильность')
                        ->options([
                            'Low' => 'Низкая',
                            'Medium' => 'Средняя',
                            'High' => 'Высокая',
                            'Very High' => 'Очень высокая',
                        ])
                        ->helperText('Подзаголовок под H1 (строчными) и строка Risk profile. В базе значение остаётся английским: Low, Medium, High, Very High.'),
                    TextInput::make('stake_range')->label('Диапазон ставки')->helperText($hint)->columnSpanFull(),
                    TextInput::make('technology')->label('Технология')->placeholder('JS, HTML5')->helperText($hint),
                    Select::make('wild_symbol')->label('Символ Wild')->options(self::yesNoOptions())->helperText($hint),
                    Select::make('free_spins')->label('Фриспины')->options(self::yesNoOptions())->helperText($hint),
                    Select::make('progressive')->label('Прогрессивный джекпот')->options(self::yesNoOptions())->helperText($hint),
                    Select::make('bonus_buy')->label('Покупка бонуса')->options(self::yesNoOptions())->helperText($hint),
                    Select::make('tumbling_wins')->label('Каскады')->options(self::yesNoOptions())->helperText($hint),
                    Select::make('gamble_feature')->label('Гэмбл')->options(self::yesNoOptions())->helperText($hint),
                    Select::make('scatter_symbol')->label('Символ Scatter')->options(self::yesNoOptions())->helperText($hint),
                    Textarea::make('features_text')
                        ->label('Фичи (текст)')
                        ->rows(3)
                        ->helperText('Одна строка Features через запятую. Без автоссылок — пишите готовый текст.')
                        ->columnSpanFull(),
                    Textarea::make('theme_text')
                        ->label('Тема (текст)')
                        ->rows(2)
                        ->helperText('Одна строка Theme через запятую. Без автоссылок.')
                        ->columnSpanFull(),
                ]),
            Section::make('Флаги для фильтров Free Slots')
                ->description('Включайте то, по чему слот потом должен находиться в каталоге. Согласуйте с ответами «Да» выше.')
                ->columns(3)
                ->schema([
                    Toggle::make('filter_bonus_buy')->label('Фильтр: покупка бонуса'),
                    Toggle::make('filter_free_spins')->label('Фильтр: фриспины'),
                    Toggle::make('filter_tumbling')->label('Фильтр: каскады'),
                    Toggle::make('filter_scatter')->label('Фильтр: scatter'),
                    Toggle::make('filter_gamble')->label('Фильтр: гэмбл'),
                    Toggle::make('filter_progressive')->label('Фильтр: прогрессив'),
                ]),
        ];
    }

    protected static function localeContentFields(string $locale, string $label): array
    {
        $L = fn (string $name) => "{$name}.{$locale}";
        $req = $locale === 'en';

        return [
            Section::make("SEO и вступление ({$label})")
                ->columns(2)
                ->schema([
                    TextInput::make($L('title'))
                        ->label("Название слота ({$label})")
                        ->required($req)
                        ->helperText($locale === 'en' ? 'Если slug пустой, адрес страницы берётся отсюда.' : 'Название слота на этом языке.'),
                    TextInput::make($L('meta_title'))->label('SEO-заголовок')->helperText('Необязательно. Если пусто, берётся H1.'),
                    Textarea::make($L('meta_description'))->label('SEO-описание')->rows(2)->columnSpanFull(),
                    Textarea::make($L('intro'))
                        ->label('Текст под H1 (необязательно)')
                        ->rows(2)
                        ->helperText('Если пусто, соберётся само: провайдер, RTP, волатильность и максимальный выигрыш.')
                        ->columnSpanFull(),
                    Textarea::make($L('excerpt'))->label('Короткий анонс')->rows(2)->columnSpanFull(),
                ]),
            Section::make('Краткий вердикт и описание')
                ->schema([
                    RichEditor::make($L('quick_verdict'))
                        ->label('Краткий вердикт')
                        ->helperText('Уникальный текст справа от демо. Первый абзац — жирный заголовок, дальше — пояснение. Подписи Quick verdict, Best for, Risk profile, Default RTP и ссылка Read the independent verdict не меняются.'),
                    RichEditor::make($L('about_text'))
                        ->label('О слоте')
                        ->helperText('Уникальный абзац в шапке. Заголовок H2 — название слота, оценка — поле «Наша оценка», строка 18+ и кнопка Read full review не меняются.'),
                    Textarea::make($L('rtp_blurb'))
                        ->label('Текст карточки RTP')
                        ->rows(3)
                        ->helperText('Уникальный абзац слева в блоке RTP. Заголовок собирается из названия слота и полей RTP от/до. Две ссылки GUIDE не редактируются: общий гайд и гайд провайдера.'),
                ]),
            Section::make('Обзор')
                ->schema([
                    RichEditor::make($L('review_overview'))
                        ->label('Текст обзора')
                        ->helperText('Уникальный абзац. Заголовок на сайте: «{название} review overview». Подпись Pros and limitations не меняется.'),
                    Textarea::make($L('pros'))
                        ->label('Pros')
                        ->rows(2)
                        ->helperText('Одна строка после «Pros:». Без списка и без слова Pros — оно уже на сайте.'),
                    Textarea::make($L('cons'))
                        ->label('Limitations')
                        ->rows(2)
                        ->helperText('Одна строка после «Limitations:». Без списка и без слова Limitations.'),
                ]),
            Section::make('Кому подойдёт')
                ->schema([
                    Textarea::make($L('audience_intro'))
                        ->label('Вступление')
                        ->rows(4)
                        ->helperText('Уникальный абзац. Заголовок на сайте: «Who {название} suits». Подписи Best for и Not ideal for не меняются.'),
                    Textarea::make($L('best_for'))
                        ->label('Best for (каждый пункт с новой строки)')
                        ->rows(4)
                        ->helperText('Каждая строка — пункт с зелёной галочкой. Первая строка также показывается в верхнем блоке рядом с Quick verdict.'),
                    Textarea::make($L('not_ideal_for'))
                        ->label('Not ideal for (каждый пункт с новой строки)')
                        ->rows(4)
                        ->helperText('Каждая строка — пункт с красным крестиком в правой колонке.'),
                ]),
            Section::make('Бонусные функции')
                ->schema([
                    RichEditor::make($L('bonus_features_body'))
                        ->label('Текст блока')
                        ->helperText('H2 на сайте: «How {название} bonus features work». Картинки вставляются в текст. Серый блок как Kyle’s Take — цитата. Словосочетания free spins, bonus buy, tumbling wins, scatter symbol, gamble feature, progressive jackpot сами станут ссылками на фильтры By Feature.'),
                ]),
            Section::make('Опыт / сессия')
                ->schema([
                    TextInput::make($L('experience_title'))
                        ->label('Заголовок опыта (H2)')
                        ->helperText('H2 всегда уникальный. Шаблона нет: пишите целиком, например «My 50 Free Spins Experience - Gates of Olympus».'),
                    RichEditor::make($L('experience_body'))
                        ->label('Текст опыта')
                        ->helperText('Обычный текстовый блок. Картинку или видео вставляйте в текст.'),
                ]),
            Section::make('Как играть ответственно')
                ->schema([
                    Textarea::make($L('responsible_play'))
                        ->label('Вступление')
                        ->rows(4)
                        ->helperText('Уникальный абзац под H2. Заголовок на сайте: «How to play {название} responsibly».'),
                    Repeater::make($L('responsible_steps'))
                        ->label('Шаги')
                        ->helperText('Нумерация 01. 02. ставится сама. Количество любое.')
                        ->schema([
                            TextInput::make('title')->label('Заголовок шага')->required(),
                            Textarea::make('body')->label('Пояснение')->rows(3),
                        ])
                        ->defaultItems(0)
                        ->collapsible()
                        ->reorderable()
                        ->formatStateUsing(fn ($state) => is_array($state) ? $state : []),
                ]),
            Section::make('Symbols and Paytable')
                ->description('H2 на сайте всегда: «Symbols and Paytable». Карточки и таблица — вкладка «Символы», они общие для всех языков.')
                ->schema([
                    Textarea::make($L('symbols_intro'))
                        ->label('Текст сверху')
                        ->rows(2)
                        ->helperText('Уникальная строка над карточками.'),
                    Textarea::make($L('symbols_mid'))
                        ->label('Текст между карточками и таблицей')
                        ->rows(3)
                        ->helperText('Необязательно. Показывается только если заполнен.'),
                    Textarea::make($L('symbols_outro'))
                        ->label('Текст под таблицей')
                        ->rows(2)
                        ->helperText('Необязательно.'),
                ]),
            Section::make('RTP, волатильность и max win')
                ->schema([
                    Textarea::make($L('rtp_section_text'))
                        ->label('Уникальный абзац')
                        ->rows(5)
                        ->helperText('H2 на сайте всегда: «{название} RTP, volatility and maximum win». Четыре ячейки (Provider, RTP range, Volatility, Max win) берутся сами из таблицы «Об игре» — сюда их не пишите.'),
                    Textarea::make("interpret_cards.{$locale}.0.body")
                        ->label('Dry-spell test')
                        ->rows(3)
                        ->helperText('Заголовок на сайте всегда «Dry-spell test». Пишите только уникальный текст.'),
                    Textarea::make("interpret_cards.{$locale}.1.body")
                        ->label('Mobile panel check')
                        ->rows(3)
                        ->helperText('Заголовок всегда «Mobile panel check».'),
                    Textarea::make("interpret_cards.{$locale}.2.body")
                        ->label('Low-RTP warning')
                        ->rows(3)
                        ->helperText('Заголовок всегда «Low-RTP warning». Подзаголовок блока: «How to interpret the numbers» — не редактируется.'),
                ]),
            Section::make('Скриншоты')
                ->schema([
                    Textarea::make($L('screenshots_intro'))
                        ->label('Вступление к скриншотам')
                        ->rows(4)
                        ->helperText('H2 на сайте всегда: «{название} Screenshots». Картинки и подписи — вкладка «Скриншоты» (подписи на каждом языке отдельно).'),
                    Textarea::make($L('mobile_intro'))
                        ->label('Вступление: слот на мобильном')
                        ->rows(6)
                        ->helperText('H2 на сайте всегда: «{название} on mobile». Пустая строка между абзацами = два абзаца. Картинки и подписи — вкладка «Скриншоты».'),
                    Textarea::make($L('mobile_checklist'))
                        ->label('Чеклист (каждый пункт с новой строки)')
                        ->rows(6)
                        ->helperText('H3 всегда: «Mobile slot checklist». Формат «Readable grid: текст» — первая часть жирная. Без двоеточия — обычная строка.'),
                ]),
            Section::make('Похожие слоты')
                ->description('H2 на сайте: «{название} vs similar {провайдер} slots». Таблица с тем же заголовком. Строки — этот слот и случайные опубликованные того же провайдера. RTP / max win и волатильность только из «Об игре» (диапазон RTP от/до или число RTP, плюс max win). Bonus fit и Watch out — уникальные поля ревью, пишутся один раз и подтягиваются в чужие таблицы.')
                ->schema([
                    Textarea::make($L('similar_intro'))
                        ->label('Текст над таблицей')
                        ->rows(4)
                        ->helperText('Уникальный абзац под H2.'),
                    Textarea::make($L('similar_bonus_fit'))
                        ->label('Bonus fit')
                        ->rows(2)
                        ->helperText('Короткая уникальная строка про бонус этого слота. Попадёт в его строку в таблице сравнения — и на этой странице, и на страницах других слотов того же провайдера.'),
                    Textarea::make($L('similar_watch_out'))
                        ->label('Watch out for')
                        ->rows(2)
                        ->helperText('Короткая уникальная строка, что учесть в этом слоте. Тоже переиспользуется в таблицах других слотов провайдера.'),
                    Textarea::make($L('similar_outro'))
                        ->label('Текст «Read more about {провайдер} slots»')
                        ->rows(4)
                        ->helperText('H3 собирается сам. Карточка публикации подтянется сама, если в заголовке опубликованной публикации есть имя этого провайдера.'),
                    Textarea::make($L('similar_new_intro'))
                        ->label('Текст «New {провайдер} slots to compare after {название}»')
                        ->rows(4)
                        ->helperText('H3 собирается сам. Карусель ниже — последние добавленные слоты этого провайдера, не вручную.'),
                    Textarea::make($L('similar_note'))
                        ->label('Примечание под каруселью')
                        ->rows(2)
                        ->helperText('Серая строка внизу блока. Если пусто — не показывается.'),
                ]),
            Section::make('Как проверяли обзор')
                ->description('H2 на сайте всегда: «How this {название} review was checked». Весь текст уникальный. Блок Need support? шаблонный и появляется сам, если блок заполнен.')
                ->schema([
                    Textarea::make($L('methodology_status'))
                        ->label('Publication status')
                        ->rows(2)
                        ->helperText('Только уникальная часть. На сайте слева жирное «Publication status:».'),
                    Repeater::make($L('methodology_points'))
                        ->label('Пункты проверки')
                        ->helperText('Заголовок на сайте жирный с точкой, дальше уникальный текст. Порядок любой.')
                        ->schema([
                            TextInput::make('title')->label('Заголовок')->required(),
                            Textarea::make('body')->label('Текст')->rows(3)->required(),
                        ])
                        ->defaultItems(0)
                        ->collapsible()
                        ->reorderable()
                        ->formatStateUsing(fn ($state) => is_array($state) ? $state : []),
                ]),
            Section::make('FAQ')
                ->schema([
                    Repeater::make($L('faq'))
                        ->label('Вопросы и ответы')
                        ->schema([
                            TextInput::make('question')->label('Вопрос')->required(),
                            Textarea::make('answer')->label('Ответ')->rows(3)->required(),
                        ])
                        ->defaultItems(0)
                        ->collapsible()
                        ->formatStateUsing(fn ($state) => is_array($state) ? $state : []),
                ]),
        ];
    }

    protected static function symbolsMediaFields(): array
    {
        return [
            Section::make('Карточки символов')
                ->description('Картинки общие для EN/DE/FR. Можно показать только карточки, только таблицу или оба блока.')
                ->schema([
                    Repeater::make('symbols')
                        ->label('Карточки')
                        ->helperText('Квадратная картинка + название. Справа либо строки выплат (x5 5000), либо обычный текст.')
                        ->schema([
                            FilamentR2::prepare(
                                FileUpload::make('image')
                                    ->label('Картинка символа')
                                    ->directory('slots/symbols')
                                    ->image()
                                    ->maxSize(4096)
                                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state[0] ?? null) : $state)
                            ),
                            TextInput::make('name')->label('Название символа')->required(),
                            Textarea::make('payout_text')
                                ->label('Выплаты строками')
                                ->rows(4)
                                ->helperText('Каждая строка: x5 5000'),
                            Textarea::make('description')
                                ->label('Или текст вместо строк')
                                ->rows(2)
                                ->helperText('Если строки выплат пустые, рядом с картинкой будет этот текст.'),
                        ])
                        ->defaultItems(0)
                        ->collapsible()
                        ->reorderable()
                        ->columnSpanFull(),
                ]),
            Section::make('Таблица выплат')
                ->schema([
                    TextInput::make('paytable_headers.symbol')
                        ->label('Заголовок колонки символа')
                        ->placeholder('Symbol'),
                    Repeater::make('paytable_headers.columns')
                        ->label('Остальные заголовки')
                        ->helperText('Любое количество, цифры или слова. Без бокового скролла на сайте.')
                        ->simple(TextInput::make('label')->required())
                        ->defaultItems(0)
                        ->columnSpanFull(),
                    Repeater::make('paytable_rows')
                        ->label('Строки')
                        ->helperText('Отдельная картинка для таблицы. Можно без картинки — тогда название. Ячейки слева направо по заголовкам.')
                        ->schema([
                            FilamentR2::prepare(
                                FileUpload::make('image')
                                    ->label('Картинка в таблице')
                                    ->directory('slots/paytable')
                                    ->image()
                                    ->maxSize(4096)
                                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state[0] ?? null) : $state)
                            ),
                            TextInput::make('label')->label('Название, если нет картинки (или подпись)'),
                            Repeater::make('cells')
                                ->label('Ячейки')
                                ->simple(TextInput::make('value'))
                                ->defaultItems(0),
                        ])
                        ->defaultItems(0)
                        ->collapsible()
                        ->reorderable()
                        ->columnSpanFull(),
                ]),
        ];
    }

    protected static function shotCaptionFields(): array
    {
        return [
            TextInput::make('caption.en')
                ->label('Подпись (английский)')
                ->helperText('На EN-странице. Если немецкая или французская пустые — возьмётся эта.'),
            TextInput::make('caption.de')->label('Подпись (немецкий)'),
            TextInput::make('caption.fr')->label('Подпись (французский)'),
        ];
    }

    protected static function screenshotFileUpload(string $directory): FileUpload
    {
        return FilamentR2::prepare(
            FileUpload::make('path')
                ->label('Картинка')
                ->directory($directory)
                ->image()
                ->maxSize(8192)
                ->downloadable()
                ->openable()
                ->required()
                ->helperText('Файл сохраняется в Cloudflare R2.')
                ->formatStateUsing(fn ($state) => is_array($state) ? ($state[0] ?? null) : $state)
        );
    }

    protected static function mediaFields(): array
    {
        return [
            Tabs::make('Виды скриншотов')
                ->tabs([
                    Tab::make('Десктоп')
                        ->schema([
                            Repeater::make('screenshots')
                                ->label('Скриншоты игры')
                                ->helperText('Блок «{название} Screenshots». Картинки общие, подписи — на каждом языке. Шаблонов в коде нет: что напишете, то и на сайте.')
                                ->schema([
                                    self::screenshotFileUpload('slots/screenshots'),
                                    ...self::shotCaptionFields(),
                                ])
                                ->defaultItems(0)
                                ->addActionLabel('Добавить скриншот')
                                ->collapsible()
                                ->cloneable()
                                ->reorderable()
                                ->itemLabel(fn (?array $state): ?string => filled($state['caption']['en'] ?? null) ? (string) $state['caption']['en'] : 'Скриншот'),
                        ]),
                    Tab::make('Мобильные')
                        ->schema([
                            Repeater::make('mobile_screenshots')
                                ->label('Мобильные скриншоты')
                                ->helperText('Блок «{название} on mobile». Портретные кадры. Подписи тоже EN / DE / FR, без шаблона в коде.')
                                ->schema([
                                    self::screenshotFileUpload('slots/mobile'),
                                    ...self::shotCaptionFields(),
                                ])
                                ->defaultItems(0)
                                ->addActionLabel('Добавить мобильный скриншот')
                                ->collapsible()
                                ->cloneable()
                                ->reorderable()
                                ->itemLabel(fn (?array $state): ?string => filled($state['caption']['en'] ?? null) ? (string) $state['caption']['en'] : 'Мобильный скриншот'),
                        ]),
                ]),
        ];
    }
}
