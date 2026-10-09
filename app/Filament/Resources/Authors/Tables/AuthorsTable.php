<?php

namespace App\Filament\Resources\Authors\Tables;

use App\Filament\Support\TranslatableSort;
use App\Models\Author;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AuthorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->getStateUsing(fn (Author $record): ?string => $record->avatarUrl()),
                TextColumn::make('name')->label('Имя')->searchable()->sortable(query: TranslatableSort::by('name')),
                TextColumn::make('position')
                    ->label('Должность')
                    ->getStateUsing(fn (Author $record): ?string => $record->positionLabel('en'))
                    ->placeholder('—'),
                TextColumn::make('slug')->label('Адрес')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('slots_count')->label('Слоты')->counts('slots')->alignCenter(),
                TextColumn::make('posts_count')->label('Публикации')->counts('posts')->alignCenter(),
                IconColumn::make('is_published')->label('На сайте')->boolean(),
                TextColumn::make('sort_order')->label('Порядок')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Обновлён')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('На сайте')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Author $record): string => rtrim($record->publicUrl('en'), '/').'/')
                    ->openUrlInNewTab()
                    ->visible(fn (Author $record): bool => $record->is_published),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
