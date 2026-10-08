<?php

namespace App\Filament\Resources\PageBlocks\Pages;

use App\Filament\Resources\PageBlocks\PageBlockResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPageBlock extends EditRecord
{
    protected static string $resource = PageBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
