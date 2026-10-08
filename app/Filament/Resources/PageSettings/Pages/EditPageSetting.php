<?php

namespace App\Filament\Resources\PageSettings\Pages;

use App\Filament\Resources\PageSettings\PageSettingResource;
use App\Models\PageSetting;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditPageSetting extends EditRecord
{
    protected static string $resource = PageSettingResource::class;

    protected function getHeaderActions(): array
    {
        /** @var PageSetting $record */
        $record = $this->getRecord();

        return [
            Action::make('open')
                ->label('Открыть на сайте')
                ->url(url($record->path()))
                ->openUrlInNewTab(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->normalize($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->normalize($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        foreach (PageSetting::LOCALES as $locale) {
            $faq = $data['faq'][$locale] ?? null;
            $data['faq'][$locale] = array_values(is_array($faq) ? $faq : []);

            foreach (['meta_title', 'meta_description'] as $field) {
                $data[$field][$locale] = trim((string) ($data[$field][$locale] ?? ''));
            }
        }

        return $data;
    }
}
