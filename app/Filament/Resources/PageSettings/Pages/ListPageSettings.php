<?php

namespace App\Filament\Resources\PageSettings\Pages;

use App\Filament\Resources\PageSettings\PageSettingResource;
use App\Models\PageSetting;
use Filament\Resources\Pages\ListRecords;

class ListPageSettings extends ListRecords
{
    protected static string $resource = PageSettingResource::class;

    public function mount(): void
    {
        PageSetting::syncRegistry();

        parent::mount();
    }
}
