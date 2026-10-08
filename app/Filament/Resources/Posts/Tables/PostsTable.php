<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Filament\Support\TranslatableSort;
use App\Models\Post;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Название')->searchable()->sortable(query: TranslatableSort::by('title')),
                TextColumn::make('type')
                    ->label('Раздел')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Post::typeOptions()[$state] ?? (string) $state),
                TextColumn::make('slug')->label('Адрес')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('author.slug')
                    ->label('Автор')
                    ->formatStateUsing(fn ($state, Post $record): string => $record->author?->displayName('en') ?: '—'),
                TextColumn::make('likes_count')->label('Лайки')->sortable()->alignCenter(),
                IconColumn::make('is_published')->label('На сайте')->boolean(),
                IconColumn::make('is_featured')->label('Избранное')->boolean(),
                TextColumn::make('created_at')->label('Создана')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('content_updated_on')->label('Обновлена на сайте')->date('d.m.Y')->sortable()->placeholder('—'),
                TextColumn::make('updated_at')->label('Изменена в админке')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Раздел')
                    ->options(Post::typeOptions()),
                TernaryFilter::make('is_published')
                    ->label('На сайте')
                    ->trueLabel('Опубликованные')
                    ->falseLabel('Черновики')
                    ->placeholder('Все'),
                TernaryFilter::make('is_featured')
                    ->label('Избранное')
                    ->placeholder('Все'),
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
