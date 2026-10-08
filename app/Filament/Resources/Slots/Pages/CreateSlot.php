<?php

namespace App\Filament\Resources\Slots\Pages;

use App\Filament\Resources\Slots\SlotResource;
use App\Models\Slot;
use Filament\Resources\Pages\CreateRecord;

class CreateSlot extends CreateRecord
{
    protected static string $resource = SlotResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (blank($data['slug'] ?? null) && filled(data_get($data, 'title.en'))) {
            $data['slug'] = Slot::makeSlugFromTitle((string) data_get($data, 'title.en'));
        }

        foreach (['en', 'de', 'fr'] as $locale) {
            $interpret = data_get($data, "interpret_cards.{$locale}");
            data_set($data, "interpret_cards.{$locale}", Slot::normalizeInterpretCards(is_array($interpret) ? $interpret : []));
        }

        foreach (['screenshots', 'mobile_screenshots'] as $key) {
            $shots = is_array($data[$key] ?? null) ? $data[$key] : [];
            $data[$key] = array_map(function ($shot) {
                if (! is_array($shot)) {
                    return $shot;
                }
                $shot['caption'] = Slot::normalizeShotCaption($shot['caption'] ?? null);

                return $shot;
            }, $shots);
        }

        return $data;
    }
}
