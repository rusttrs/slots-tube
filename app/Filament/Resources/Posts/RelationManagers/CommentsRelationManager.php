<?php

namespace App\Filament\Resources\Posts\RelationManagers;

use App\Filament\Actions\LikersAction;
use App\Models\PostComment;
use App\Support\FilamentR2;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $title = 'Комментарии';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Комментарии';
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('user'))
            ->defaultSort('created_at', 'desc')
            ->recordTitleAttribute('body')
            ->heading('Комментарии')
            ->description('Как отзывы у слота: модерация только здесь, на карточке публикации. Поиск по тексту, имени и почте.')
            ->columns([
                TextColumn::make('user')
                    ->label('Автор')
                    ->getStateUsing(fn (PostComment $record): string => $record->user?->displayName() ?: '—')
                    ->description(fn (PostComment $record): ?string => $record->user?->email)
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
                TextColumn::make('body')
                    ->label('Текст')
                    ->limit(80)
                    ->wrap()
                    ->searchable(),
                TextColumn::make('parent_id')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state ? 'Ответ' : 'Комментарий')
                    ->color(fn ($state): string => $state ? 'gray' : 'info'),
                TextColumn::make('likes_count')
                    ->label('Лайки')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'warning' : 'gray')
                    ->tooltip('Нажмите, чтобы увидеть, кто лайкнул этот комментарий')
                    ->action(LikersAction::make('Кто лайкнул комментарий')),
                IconColumn::make('is_published')->boolean()->label('На сайте'),
                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_published')
                    ->label('На сайте')
                    ->trueLabel('Видимые')
                    ->falseLabel('Скрытые')
                    ->placeholder('Все'),
                TernaryFilter::make('parent_id')
                    ->label('Тип')
                    ->placeholder('Все')
                    ->trueLabel('Ответы')
                    ->falseLabel('Комментарии')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('parent_id'),
                        false: fn (Builder $query): Builder => $query->whereNull('parent_id'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Модерировать')
                    ->schema([
                        Textarea::make('body')->label('Текст')->rows(5),
                        FilamentR2::prepare(
                            FileUpload::make('image_path')
                                ->label('Картинка')
                                ->image()
                                ->directory('comments')
                                ->maxSize(4096)
                        ),
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
