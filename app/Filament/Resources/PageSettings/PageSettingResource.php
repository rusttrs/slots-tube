<?php

namespace App\Filament\Resources\PageSettings;

use App\Filament\Resources\PageSettings\Pages\EditPageSetting;
use App\Filament\Resources\PageSettings\Pages\ListPageSettings;
use App\Filament\Resources\PageSettings\Schemas\PageSettingForm;
use App\Filament\Resources\PageSettings\Tables\PageSettingsTable;
use App\Models\PageSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PageSettingResource extends Resource
{
    protected static ?string $model = PageSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $modelLabel = 'страница';

    protected static ?string $pluralModelLabel = 'Страницы: SEO и FAQ';

    protected static string|\UnitEnum|null $navigationGroup = 'Контент';

    protected static ?string $navigationLabel = 'Страницы: SEO и FAQ';

    protected static ?string $slug = 'page-settings';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return PageSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PageSettingsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->registered();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record instanceof PageSetting ? $record->label() : '';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPageSettings::route('/'),
            'edit' => EditPageSetting::route('/{record}/edit'),
        ];
    }
}
