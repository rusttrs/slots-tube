<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label('На сайте')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(function (): ?string {
                    $record = $this->getRecord();
                    if (! $record instanceof Post || blank($record->slug)) {
                        return null;
                    }

                    return rtrim($record->publicUrl(), '/').'/';
                })
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->getRecord() instanceof Post && filled($this->getRecord()->slug)),
            DeleteAction::make(),
        ];
    }
}
