<?php

namespace App\Filament\Resources\Slots\RelationManagers;

use App\Filament\Actions\LikersAction;
use App\Models\SlotReview;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ReviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'reviews';

    protected static ?string $title = 'Отзывы игроков';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Отзывы игроков';
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['user']))
            ->defaultSort('created_at', 'desc')
            ->recordTitleAttribute('body')
            ->heading('Отзывы игроков')
            ->description('Поиск по тексту, имени и почте. Фильтры слева: публикация, демо / real money, оценка.')
            ->columns([
                TextColumn::make('user')
                    ->label('Игрок')
                    ->getStateUsing(fn (SlotReview $record): string => $record->user?->displayName() ?: '—')
                    ->description(fn (SlotReview $record): ?string => $record->user?->email)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $term = '%'.$search.'%';

                        return $query->where(function (Builder $q) use ($term): void {
                            $q->where('body', 'ilike', $term)
                                ->orWhereHas('user', function (Builder $user) use ($term): void {
                                    $user->where('nickname', 'ilike', $term)
                                        ->orWhere('name', 'ilike', $term)
                                        ->orWhere('email', 'ilike', $term);
                                });
                        });
                    }),
                TextColumn::make('play_mode')
                    ->label('Режим')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state === 'real' ? 'Real money' : 'Demo')
                    ->color(fn (?string $state): string => $state === 'real' ? 'warning' : 'gray'),
                TextColumn::make('rating')
                    ->label('Оценка')
                    ->sortable()
                    ->formatStateUsing(fn (?int $state): string => ($state ?? 0).'/5'),
                TextColumn::make('body')
                    ->label('Текст')
                    ->limit(80)
                    ->wrap()
                    ->searchable(),
                TextColumn::make('likes_count')
                    ->label('Лайки')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray')
                    ->tooltip('Нажмите, чтобы увидеть, кто лайкнул этот отзыв')
                    ->action(LikersAction::make('Кто лайкнул отзыв')),
                IconColumn::make('played_myself')->boolean()->label('Играл сам'),
                IconColumn::make('is_published')->boolean()->label('На сайте'),
                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_published')
                    ->label('На сайте')
                    ->trueLabel('Опубликованные')
                    ->falseLabel('Скрытые')
                    ->placeholder('Все'),
                SelectFilter::make('play_mode')
                    ->label('Режим')
                    ->options([
                        'demo' => 'Demo',
                        'real' => 'Real money',
                    ]),
                SelectFilter::make('rating')
                    ->label('Оценка')
                    ->options([
                        5 => '5',
                        4 => '4',
                        3 => '3',
                        2 => '2',
                        1 => '1',
                    ]),
                TernaryFilter::make('played_myself')
                    ->label('Играл сам')
                    ->placeholder('Все'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Модерировать')
                    ->schema([
                        Select::make('play_mode')
                            ->label('Режим')
                            ->options([
                                'demo' => 'Demo play',
                                'real' => 'Real money',
                            ])
                            ->required(),
                        Select::make('rating')
                            ->label('Оценка')
                            ->options([
                                1 => '1',
                                2 => '2',
                                3 => '3',
                                4 => '4',
                                5 => '5',
                            ])
                            ->required(),
                        Textarea::make('body')->label('Текст')->rows(5),
                        Toggle::make('played_myself')->label('Играл сам'),
                        Toggle::make('is_published')->label('Показывать на сайте'),
                    ]),
                DeleteAction::make()->label('Удалить'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
