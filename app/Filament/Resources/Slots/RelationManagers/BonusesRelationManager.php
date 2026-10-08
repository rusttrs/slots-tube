<?php

namespace App\Filament\Resources\Slots\RelationManagers;

use App\Filament\Resources\Bonuses\Schemas\BonusForm;
use App\Models\Bonus;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BonusesRelationManager extends RelationManager
{
    protected static string $relationship = 'bonuses';

    protected static ?string $title = 'Бонусы казино на этой странице';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Бонусы казино на этой странице';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(BonusForm::cardFields(withSort: true));
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('casino_name')
            ->reorderable('sort_order')
            ->heading('Бонусы казино на этой странице')
            ->description('Карточки блока Where to play. Тексты вокруг карточек статичные. Порядок закреплён за слотом и не меняется при обновлении сайта.')
            ->columns([
                TextColumn::make('casino_name')->label('Название казино')->searchable(),
                TextColumn::make('short_text')->label('Краткое описание'),
                TextColumn::make('extra_text')->label('Доп. описание'),
                TextColumn::make('cta_url')->label('Реф. ссылка')->limit(24),
                TextColumn::make('website_url')->label('Сайт')->limit(24)->toggleable(),
                IconColumn::make('is_hot')->boolean()->label('Hot'),
                IconColumn::make('is_new')->boolean()->label('New'),
                TextColumn::make('countries')
                    ->label('Страны')
                    ->formatStateUsing(function ($state): string {
                        $options = Bonus::countryOptions();
                        $codes = is_array($state) ? $state : (filled($state) ? [$state] : [Bonus::allCountriesCode()]);

                        return collect($codes)
                            ->map(fn ($code) => $options[strtoupper((string) $code)] ?? strtoupper((string) $code))
                            ->implode(', ');
                    })
                    ->wrap()
                    ->limit(40),
                IconColumn::make('is_published')->boolean()->label('На сайте'),
                TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable(query: fn ($query, string $direction) => $query->orderBy('bonus_slot.sort_order', $direction)),
            ])
            ->headerActions([
                Action::make('createCard')
                    ->label('Добавить карточку')
                    ->schema(BonusForm::cardFields(withSort: true))
                    ->action(function (array $data): void {
                        $sort = (int) ($data['sort_order'] ?? 0);
                        unset($data['sort_order']);
                        $data['is_published'] = $data['is_published'] ?? true;
                        $data['countries'] = $data['countries'] ?? [Bonus::allCountriesCode()];
                        $bonus = Bonus::query()->create($data);
                        $this->getOwnerRecord()->bonuses()->attach($bonus->getKey(), [
                            'sort_order' => $sort,
                        ]);
                    }),
                Action::make('attachExisting')
                    ->label('Прикрепить существующий')
                    ->color('gray')
                    ->schema([
                        Select::make('bonus_id')
                            ->label('Бонус')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->options(function (): array {
                                $slotId = $this->getOwnerRecord()->getKey();

                                // Без DISTINCT/join Filament Attach — PG не умеет DISTINCT по json-полям бонуса.
                                return Bonus::query()
                                    ->where('is_published', true)
                                    ->whereDoesntHave(
                                        'slots',
                                        fn ($q) => $q->where('slots.id', $slotId)
                                    )
                                    ->orderBy('casino_name')
                                    ->orderBy('id')
                                    ->pluck('casino_name', 'id')
                                    ->all();
                            })
                            ->helperText('Уже созданная карточка из раздела «Бонусы». Порядок на этой странице слота задаётся ниже.'),
                        TextInput::make('sort_order')->label('Порядок')->numeric()->default(0),
                    ])
                    ->action(function (array $data): void {
                        $this->getOwnerRecord()->bonuses()->syncWithoutDetaching([
                            (int) $data['bonus_id'] => [
                                'sort_order' => (int) ($data['sort_order'] ?? 0),
                            ],
                        ]);
                    }),
            ])
            ->recordActions([
                Action::make('editCard')
                    ->label('Изменить')
                    ->schema(BonusForm::cardFields(withSort: true))
                    ->fillForm(function (Bonus $record): array {
                        return [
                            'casino_name' => $record->casino_name,
                            'short_text' => $record->getTranslations('short_text'),
                            'extra_text' => $record->extra_text,
                            'cta_url' => $record->cta_url,
                            'website_url' => $record->website_url,
                            'features' => $record->featureKeys(),
                            'is_hot' => (bool) $record->is_hot,
                            'is_new' => (bool) $record->is_new,
                            'countries' => $record->countryCodes(),
                            'is_published' => (bool) $record->is_published,
                            'sort_order' => (int) ($record->pivot?->sort_order ?? 0),
                        ];
                    })
                    ->action(function (Bonus $record, array $data): void {
                        $sort = (int) ($data['sort_order'] ?? 0);
                        unset($data['sort_order']);
                        $data['countries'] = $data['countries'] ?? [Bonus::allCountriesCode()];
                        $record->update($data);
                        $this->getOwnerRecord()->bonuses()->updateExistingPivot($record->getKey(), [
                            'sort_order' => $sort,
                        ]);
                    }),
                DetachAction::make()->label('Открепить'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
