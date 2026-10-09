<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use App\Support\FilamentR2;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Профиль')
                ->columns(2)
                ->schema([
                    FilamentR2::prepare(
                        FileUpload::make('avatar_path')
                            ->label('Аватар')
                            ->directory('avatars')
                            ->image()
                            ->avatar()
                            ->imageEditor()
                            ->maxSize(2048)
                            ->columnSpanFull()
                    ),
                    TextInput::make('name')
                        ->label('Имя')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('nickname')
                        ->label('Ник')
                        ->maxLength(40),
                    TextInput::make('email')
                        ->label('Почта')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                ]),

            Section::make('Вход и пароль')
                ->columns(2)
                ->schema([
                    TextInput::make('google_id')
                        ->label('Google ID')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('auth_provider_label')
                        ->label('Способ входа')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(function ($state, $record) {
                            if (! $record) {
                                return null;
                            }

                            return $record->google_id ? 'Google' : 'Почта';
                        }),
                    TextInput::make('password')
                        ->label('Новый пароль')
                        ->password()
                        ->revealable()
                        ->rule(Password::defaults())
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->dehydrateStateUsing(fn (?string $state): ?string => $state)
                        ->same('passwordConfirmation')
                        ->helperText('Оставьте пустым, чтобы не менять пароль.'),
                    TextInput::make('passwordConfirmation')
                        ->label('Повтор пароля')
                        ->password()
                        ->revealable()
                        ->dehydrated(false),
                ]),

            Section::make('Настройки')
                ->columns(2)
                ->schema([
                    Toggle::make('newsletter_opt_in')->label('Подписка на рассылку'),
                    Toggle::make('age_confirmed')->label('Подтвердил 18+'),
                    DateTimePicker::make('email_verified_at')->label('Почта подтверждена'),
                    DateTimePicker::make('onboarding_completed_at')->label('Онбординг пройден'),
                ]),

            Section::make('Статус')
                ->columns(2)
                ->schema([
                    Toggle::make('is_admin')
                        ->label('Доступ в админку')
                        ->helperText('Свой доступ снять нельзя.')
                        ->disabled(fn (?User $record): bool => $record?->id === Auth::id())
                        ->dehydrated(fn (?User $record): bool => $record?->id !== Auth::id())
                        ->columnSpanFull(),
                    DateTimePicker::make('deactivated_at')
                        ->label('Деактивирован')
                        ->helperText('Пусто — аккаунт активен. Можно выключить кнопкой «Деактивировать».'),
                    DateTimePicker::make('created_at')->label('Создан')->disabled()->dehydrated(false),
                    DateTimePicker::make('updated_at')->label('Обновлён')->disabled()->dehydrated(false),
                ]),
        ]);
    }
}
