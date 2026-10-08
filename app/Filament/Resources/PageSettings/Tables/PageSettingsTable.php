<?php

namespace App\Filament\Resources\PageSettings\Tables;

use App\Models\PageSetting;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class PageSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->defaultSort('id')
            ->defaultGroup(
                Group::make('group')
                    ->label('Группа')
                    ->getKeyFromRecordUsing(fn (PageSetting $record): string => $record->definition()['group'])
                    ->getTitleFromRecordUsing(fn (PageSetting $record): string => $record->definition()['group'])
                    ->orderQueryUsing(fn ($query) => $query)
                    ->scopeQueryByKeyUsing(fn ($query) => $query)
                    ->collapsible(),
            )
            ->columns([
                TextColumn::make('key')
                    ->label('Страница')
                    ->formatStateUsing(fn (PageSetting $record): string => $record->label())
                    ->description(fn (PageSetting $record): string => $record->path()),
                ...array_map(
                    fn (string $locale): TextColumn => TextColumn::make("seo_{$locale}")
                        ->label('SEO '.strtoupper($locale))
                        ->state(fn (PageSetting $record): string => filled($record->meta_title[$locale] ?? null) && filled($record->meta_description[$locale] ?? null)
                            ? 'заполнено'
                            : (filled($record->meta_title[$locale] ?? null) || filled($record->meta_description[$locale] ?? null) ? 'частично' : 'по умолчанию'))
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'заполнено' => 'success',
                            'частично' => 'warning',
                            default => 'gray',
                        }),
                    PageSetting::LOCALES,
                ),
                TextColumn::make('faq_count')
                    ->label('FAQ EN / DE / FR')
                    ->state(fn (PageSetting $record): string => $record->hasFaq()
                        ? implode(' / ', array_map(fn (string $locale): int => count($record->faq[$locale] ?? []), PageSetting::LOCALES))
                        : '—'),
                IconColumn::make('noindex')
                    ->label('Скрыта от поиска')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('gray'),
                TextColumn::make('updated_at')->label('Обновлено')->dateTime('d.m.Y H:i'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
