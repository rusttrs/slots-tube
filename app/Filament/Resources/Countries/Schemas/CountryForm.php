<?php

namespace App\Filament\Resources\Countries\Schemas;

use App\Models\Bonus;
use App\Models\Country;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CountryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Страна')
                ->columns(2)
                ->schema([
                    TextInput::make('code')
                        ->label('Код страны')
                        ->required()
                        ->maxLength(3)
                        ->regex('/^([A-Z]{2}|ALL)$/')
                        ->unique(ignoreRecord: true)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (?string $state, Set $set) => $set('code', strtoupper(trim((string) $state))))
                        ->validationMessages(['regex' => 'Две латинские буквы (CA, DE, GB…) или ALL.'])
                        ->helperText('ISO-код, как его передаёт Cloudflare: CA, DE, GB, US… ALL — для посетителей из стран, которых нет в списке.'),
                    TextInput::make('name')
                        ->label('Название')
                        ->required()
                        ->maxLength(100),
                    Toggle::make('is_active')
                        ->label('Активна')
                        ->default(true)
                        ->helperText('Выключенная страна не участвует в подборе: посетителям покажется список ALL.'),
                    TextInput::make('sort_order')
                        ->label('Порядок в списках')
                        ->numeric()
                        ->default(0),
                ]),
            Section::make('Рекомендуемые бонусы в поиске')
                ->description('Блок Recommended Bonuses, который открывается в поиске до ввода запроса. Порядок — перетаскиванием.')
                ->schema([
                    Repeater::make('searchBonusItems')
                        ->hiddenLabel()
                        ->relationship()
                        ->simple(
                            Select::make('bonus_id')
                                ->label('Бонус')
                                ->options(fn (): array => Bonus::query()
                                    ->orderBy('casino_name')
                                    ->pluck('casino_name', 'id')
                                    ->all())
                                ->searchable()
                                ->required()
                                ->distinct(),
                        )
                        ->orderColumn('sort_order')
                        ->reorderable()
                        ->defaultItems(0)
                        ->maxItems(Country::SEARCH_BONUS_LIMIT)
                        ->addActionLabel('Добавить бонус'),
                ]),
        ]);
    }
}
