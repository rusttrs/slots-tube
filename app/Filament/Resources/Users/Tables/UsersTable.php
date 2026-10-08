<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('avatar_path')
                    ->label('Аватар')
                    ->circular()
                    ->getStateUsing(fn (User $record): string => $record->avatarUrl()),
                TextColumn::make('display_name')
                    ->label('Имя')
                    ->getStateUsing(fn (User $record): string => $record->displayName())
                    ->searchable(query: function ($query, string $search) {
                        $query->where(function ($q) use ($search) {
                            $q->where('nickname', 'ilike', "%{$search}%")
                                ->orWhere('name', 'ilike', "%{$search}%")
                                ->orWhere('email', 'ilike', "%{$search}%");
                        });
                    })
                    ->sortable(query: fn ($query, string $direction) => $query->orderBy('nickname', $direction)),
                TextColumn::make('email')->label('Почта')->searchable()->sortable()->copyable(),
                TextColumn::make('auth_provider')
                    ->label('Вход')
                    ->badge()
                    ->getStateUsing(fn (User $record): string => $record->google_id ? 'Google' : 'Почта')
                    ->color(fn (string $state): string => $state === 'Google' ? 'info' : 'gray'),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->getStateUsing(fn (User $record): string => $record->isActive() ? 'Активен' : 'Выключен')
                    ->color(fn (string $state): string => $state === 'Активен' ? 'success' : 'danger'),
                IconColumn::make('newsletter_opt_in')->boolean()->label('Рассылка'),
                TextColumn::make('created_at')->label('Создан')->dateTime()->sortable()->toggleable(),
                TextColumn::make('deactivated_at')->label('Деактивирован')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('deactivated_at')
                    ->label('Активность')
                    ->nullable()
                    ->trueLabel('Только активные')
                    ->falseLabel('Только выключенные')
                    ->queries(
                        true: fn ($query) => $query->whereNull('deactivated_at'),
                        false: fn ($query) => $query->whereNotNull('deactivated_at'),
                        blank: fn ($query) => $query,
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('deactivate')
                    ->label('Деактивировать')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Деактивировать пользователя')
                    ->modalDescription('Пользователь не сможет войти, пока аккаунт снова не включат.')
                    ->visible(fn (User $record): bool => $record->isActive() && $record->id !== Auth::id())
                    ->action(function (User $record): void {
                        $record->deactivate();
                        Notification::make()->title('Пользователь деактивирован')->success()->send();
                    }),
                Action::make('reactivate')
                    ->label('Включить снова')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Включить пользователя')
                    ->visible(fn (User $record): bool => ! $record->isActive())
                    ->action(function (User $record): void {
                        $record->reactivate();
                        Notification::make()->title('Пользователь снова активен')->success()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('deactivate')
                        ->label('Деактивировать выбранных')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $authId = Auth::id();
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->id === $authId || ! $record->isActive()) {
                                    continue;
                                }
                                $record->deactivate();
                                $count++;
                            }
                            Notification::make()->title("Деактивировано: {$count}")->success()->send();
                        }),
                    BulkAction::make('reactivate')
                        ->label('Включить выбранных')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->isActive()) {
                                    continue;
                                }
                                $record->reactivate();
                                $count++;
                            }
                            Notification::make()->title("Включено: {$count}")->success()->send();
                        }),
                ]),
            ]);
    }
}
