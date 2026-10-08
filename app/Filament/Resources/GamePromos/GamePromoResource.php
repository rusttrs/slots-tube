<?php

namespace App\Filament\Resources\GamePromos;

use App\Filament\Resources\GamePromos\Pages\CreateGamePromo;
use App\Filament\Resources\GamePromos\Pages\EditGamePromo;
use App\Filament\Resources\GamePromos\Pages\ListGamePromos;
use App\Filament\Resources\GamePromos\Schemas\GamePromoForm;
use App\Filament\Resources\GamePromos\Tables\GamePromosTable;
use App\Models\GamePromo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GamePromoResource extends Resource
{
    protected static ?string $model = GamePromo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $modelLabel = 'попап в игре';

    protected static ?string $pluralModelLabel = 'Попапы в игре';

    protected static string|\UnitEnum|null $navigationGroup = 'Каталог';

    protected static ?string $navigationLabel = 'Попап в игре';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return GamePromoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GamePromosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGamePromos::route('/'),
            'create' => CreateGamePromo::route('/create'),
            'edit' => EditGamePromo::route('/{record}/edit'),
        ];
    }
}
