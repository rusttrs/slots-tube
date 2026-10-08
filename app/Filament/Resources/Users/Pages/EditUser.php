<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        /** @var User $record */
        $record = $this->getRecord();

        return [
            Action::make('deactivate')
                ->label('Деактивировать')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $record->isActive() && $record->id !== Auth::id())
                ->action(function () use ($record): void {
                    $record->deactivate();
                    Notification::make()->title('Пользователь деактивирован')->success()->send();
                    $this->refreshFormData([
                        'deactivated_at',
                        'updated_at',
                    ]);
                }),
            Action::make('reactivate')
                ->label('Включить снова')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => ! $record->isActive())
                ->action(function () use ($record): void {
                    $record->reactivate();
                    Notification::make()->title('Пользователь снова активен')->success()->send();
                    $this->refreshFormData([
                        'deactivated_at',
                        'updated_at',
                    ]);
                }),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['passwordConfirmation'], $data['auth_provider_label'], $data['google_id']);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Пользователь сохранён';
    }
}
