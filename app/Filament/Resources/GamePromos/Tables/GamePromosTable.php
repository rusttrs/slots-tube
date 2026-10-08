<?php

namespace App\Filament\Resources\GamePromos\Tables;

use App\Models\Bonus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GamePromosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('logo_path')
                    ->label('Лого')
                    ->disk('r2')
                    ->height(40)
                    ->square(),
                TextColumn::make('casino_name')
                    ->label('Казино')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('offer_text')
                    ->label('Оффер')
                    ->limit(40)
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
                TextColumn::make('delay_seconds')
                    ->label('Задержка')
                    ->suffix(' с')
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Приоритет')
                    ->sortable(),
                IconColumn::make('is_published')
                    ->label('На сайте')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Обновлён')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
