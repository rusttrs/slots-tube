<?php

namespace App\Filament\Resources\Slots\Tables;

use App\Filament\Support\TranslatableSort;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SlotsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                ImageColumn::make('cover_path')
                    ->label('Скрин')
                    ->disk('r2')
                    ->circular(false)
                    ->height(40),
                TextColumn::make('title')->label('Слот')->searchable()->sortable(query: TranslatableSort::by('title')),
                TextColumn::make('slug')->label('Адрес')->toggleable(),
                TextColumn::make('provider.name')->label('Провайдер')->toggleable(),
                IconColumn::make('is_published')->boolean()->label('На сайте'),
                TextColumn::make('editorial_score')->label('Оценка')->toggleable(),
                TextColumn::make('updated_at')->label('Обновлён')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_published')->label('Опубликован'),
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
