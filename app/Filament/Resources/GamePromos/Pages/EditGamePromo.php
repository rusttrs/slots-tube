<?php

namespace App\Filament\Resources\GamePromos\Pages;

use App\Filament\Resources\GamePromos\GamePromoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGamePromo extends EditRecord
{
    protected static string $resource = GamePromoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
