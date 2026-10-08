<?php

namespace App\Filament\Resources\Slots\Pages;

use App\Filament\Resources\Slots\SlotResource;
use App\Models\Slot;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSlot extends EditRecord
{
    protected static string $resource = SlotResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return 'Карточка';
    }

    protected function getHeaderActions(): array
    {
        /** @var Slot $record */
        $record = $this->getRecord();

        return [
            Action::make('open')
                ->label('Открыть на сайте')
                ->url(fn (): string => rtrim($record->publicUrl(), '/').'/')
                ->openUrlInNewTab()
                ->visible(fn (): bool => filled($record->slug)),
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach (['symbols', 'paytable_rows', 'screenshots', 'mobile_screenshots'] as $key) {
            if (! is_array($data[$key] ?? null)) {
                $data[$key] = [];
            }
        }

        foreach (['screenshots', 'mobile_screenshots'] as $key) {
            $data[$key] = array_map(function ($shot) {
                if (! is_array($shot)) {
                    return $shot;
                }
                $shot['caption'] = Slot::normalizeShotCaption($shot['caption'] ?? null);
                $path = $shot['path'] ?? $shot['image'] ?? null;
                if (is_array($path)) {
                    $path = $path[0] ?? null;
                }
                $shot['path'] = $path;

                return $shot;
            }, $data[$key]);
        }
        $headers = is_array($data['paytable_headers'] ?? null) ? $data['paytable_headers'] : [];
        if (empty($headers['columns']) || ! is_array($headers['columns'])) {
            $columns = [];
            foreach (['col1', 'col2', 'col3', 'col4'] as $key) {
                if (filled($headers[$key] ?? null)) {
                    $columns[] = $headers[$key];
                }
            }
            $headers['columns'] = $columns;
        }
        $data['paytable_headers'] = $headers;

        $data['paytable_rows'] = array_map(function ($row) {
            if (! is_array($row)) {
                return $row;
            }
            if (! empty($row['cells']) && is_array($row['cells'])) {
                return $row;
            }
            $cells = [];
            foreach (['col1', 'col2', 'col3', 'col4'] as $key) {
                $cells[] = (string) ($row[$key] ?? '');
            }
            if (collect($cells)->contains(fn ($cell) => $cell !== '')) {
                $row['cells'] = $cells;
            }

            return $row;
        }, $data['paytable_rows']);

        foreach (['en', 'de', 'fr'] as $locale) {
            foreach (['faq', 'responsible_steps'] as $key) {
                $path = "{$key}.{$locale}";
                $value = data_get($data, $path);
                if (! is_array($value)) {
                    data_set($data, $path, []);
                }
            }
            $interpret = data_get($data, "interpret_cards.{$locale}");
            data_set($data, "interpret_cards.{$locale}", Slot::normalizeInterpretCards(is_array($interpret) ? $interpret : []));
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
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
