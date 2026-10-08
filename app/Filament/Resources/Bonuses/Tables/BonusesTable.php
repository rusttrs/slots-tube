<?php

namespace App\Filament\Resources\Bonuses\Tables;

use App\Models\Bonus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BonusesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('casino_name')->label('Название казино')->searchable(),
                TextColumn::make('short_text')->label('Краткое описание'),
                TextColumn::make('extra_text')->label('Доп. описание'),
                TextColumn::make('cta_url')->label('Реф. ссылка')->limit(24)->toggleable(),
                TextColumn::make('website_url')->label('Сайт')->limit(24)->toggleable(),
                TextColumn::make('features')
                    ->label('Плашки')
                    ->formatStateUsing(function ($state): string {
                        $options = Bonus::featureOptions();
                        $keys = is_array($state) ? $state : (filled($state) ? [$state] : []);

                        return collect($keys)
                            ->map(fn ($key) => $options[(string) $key] ?? null)
                            ->filter()
                            ->implode(', ');
                    })
                    ->wrap(),
                TextColumn::make('countries')
                    ->label('Страны')
                    ->formatStateUsing(function ($state): string {
                        $options = Bonus::countryOptions();
                        $codes = is_array($state) ? $state : (filled($state) ? [$state] : [Bonus::allCountriesCode()]);

                        return collect($codes)
                            ->map(fn ($code) => $options[strtoupper((string) $code)] ?? strtoupper((string) $code))
                            ->implode(', ');
                    })
                    ->wrap(),
                TextColumn::make('slug')->label('Адрес')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('is_published')->label('На сайте')->badge(),
                TextColumn::make('updated_at')->label('Обновлён')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
