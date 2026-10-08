<?php

namespace App\Filament\Resources\NewsletterSubscribers\Tables;

use App\Models\NewsletterSubscriber;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class NewsletterSubscribersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('email')
                    ->label('Почта')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('locale')
                    ->label('Язык')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->getStateUsing(fn (NewsletterSubscriber $record): string => $record->isActive() ? 'Подписан' : 'Отписан')
                    ->color(fn (string $state): string => $state === 'Подписан' ? 'success' : 'gray'),
                TextColumn::make('created_at')
                    ->label('Подписался')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('unsubscribed_at')
                    ->label('Отписался')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Обновлён')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('unsubscribed_at')
                    ->label('Статус')
                    ->nullable()
                    ->placeholder('Все')
                    ->trueLabel('Только подписанные')
                    ->falseLabel('Только отписанные')
                    ->queries(
                        true: fn ($query) => $query->whereNull('unsubscribed_at'),
                        false: fn ($query) => $query->whereNotNull('unsubscribed_at'),
                        blank: fn ($query) => $query,
                    ),
                SelectFilter::make('locale')
                    ->label('Язык')
                    ->options([
                        'en' => 'EN',
                        'de' => 'DE',
                        'fr' => 'FR',
                    ]),
            ])
            ->recordActions([
                Action::make('unsubscribe')
                    ->label('Отписать')
                    ->icon('heroicon-o-no-symbol')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (NewsletterSubscriber $record): bool => $record->isActive())
                    ->action(function (NewsletterSubscriber $record): void {
                        $record->forceFill(['unsubscribed_at' => now()])->save();
                        Notification::make()->title('Подписчик отписан')->success()->send();
                    }),
                Action::make('resubscribe')
                    ->label('Вернуть подписку')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (NewsletterSubscriber $record): bool => ! $record->isActive())
                    ->action(function (NewsletterSubscriber $record): void {
                        $record->forceFill(['unsubscribed_at' => null])->save();
                        Notification::make()->title('Подписка восстановлена')->success()->send();
                    }),
                DeleteAction::make()->label('Удалить'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
