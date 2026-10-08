<?php

namespace App\Filament\Resources\PageBlocks;

use App\Filament\Resources\PageBlocks\Pages\CreatePageBlock;
use App\Filament\Resources\PageBlocks\Pages\EditPageBlock;
use App\Filament\Resources\PageBlocks\Pages\ListPageBlocks;
use App\Filament\Resources\PageBlocks\Schemas\PageBlockForm;
use App\Filament\Resources\PageBlocks\Tables\PageBlocksTable;
use App\Models\PageBlock;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PageBlockResource extends Resource
{
    protected static ?string $model = PageBlock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $modelLabel = 'блок страницы';

    protected static ?string $pluralModelLabel = 'Блоки страниц';

    protected static string|\UnitEnum|null $navigationGroup = 'Контент';

    protected static ?string $navigationLabel = 'Блоки страниц';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return PageBlockForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PageBlocksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPageBlocks::route('/'),
            'create' => CreatePageBlock::route('/create'),
            'edit' => EditPageBlock::route('/{record}/edit'),
        ];
    }
}
