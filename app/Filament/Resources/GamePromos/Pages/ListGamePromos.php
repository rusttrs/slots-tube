<?php

namespace App\Filament\Resources\GamePromos\Pages;

use App\Filament\Resources\GamePromos\GamePromoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGamePromos extends ListRecords
{
    protected static string $resource = GamePromoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
