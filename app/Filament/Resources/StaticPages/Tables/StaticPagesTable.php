<?php

namespace App\Filament\Resources\StaticPages\Tables;

use App\Filament\Support\TranslatableSort;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StaticPagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Название')->searchable()->sortable(query: TranslatableSort::by('title')),
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
