<?php

namespace App\Filament\Resources\Posts\RelationManagers;

use App\Models\Like;
use App\Services\LikeService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LikesRelationManager extends RelationManager
{
    protected static string $relationship = 'likes';

    protected static ?string $title = 'Лайки статьи';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Лайки статьи ('.(int) $ownerRecord->likes_count.')';
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('user'))
            ->defaultSort('created_at', 'desc')
            ->heading('Лайки статьи')
            ->description('Только лайки самой статьи. Лайки комментариев — во вкладке «Комментарии»: нажмите на число в колонке «Лайки».')
            ->columns([
                ImageColumn::make('avatar')
                    ->label('')
                    ->getStateUsing(fn (Like $record): ?string => $record->user?->avatarUrl())
                    ->imageSize(32)
                    ->square(),
                TextColumn::make('user')
                    ->label('Кто лайкнул')
                    ->getStateUsing(fn (Like $record): string => $record->user?->displayName() ?: '—')
                    ->description(fn (Like $record): ?string => $record->user?->email)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas('user', fn (Builder $user) => $user
                        ->where('nickname', 'ilike', '%'.$search.'%')
                        ->orWhere('name', 'ilike', '%'.$search.'%')
                        ->orWhere('email', 'ilike', '%'.$search.'%'))),
                TextColumn::make('created_at')
                    ->label('Когда')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('remove')
                    ->label('Удалить')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Удалить лайк?')
                    ->modalDescription('Лайк пропадёт с сайта, счётчик статьи уменьшится на 1.')
                    ->action(function (Like $record): void {
                        app(LikeService::class)->remove($record);
                        Notification::make()->title('Лайк удалён')->success()->send();
                    }),
            ]);
    }
}
