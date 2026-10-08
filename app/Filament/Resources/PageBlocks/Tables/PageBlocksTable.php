<?php

namespace App\Filament\Resources\PageBlocks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PageBlocksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')->label('Ключ')->searchable()->sortable(),
                TextColumn::make('slug')->label('Адрес')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('page')->label('Страница'),
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
