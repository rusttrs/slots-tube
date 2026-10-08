<?php

namespace App\Filament\Resources\Slots;

use App\Filament\Resources\Slots\Pages\CreateSlot;
use App\Filament\Resources\Slots\Pages\EditSlot;
use App\Filament\Resources\Slots\Pages\ListSlots;
use App\Filament\Resources\Slots\RelationManagers\BonusesRelationManager;
use App\Filament\Resources\Slots\RelationManagers\ReviewsRelationManager;
use App\Filament\Resources\Slots\Schemas\SlotForm;
use App\Filament\Resources\Slots\Tables\SlotsTable;
use App\Models\Slot;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SlotResource extends Resource
{
    protected static ?string $model = Slot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $modelLabel = 'слот';

    protected static ?string $pluralModelLabel = 'Слоты';

    protected static string|\UnitEnum|null $navigationGroup = 'Каталог';

    protected static ?string $navigationLabel = 'Слоты';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return SlotForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SlotsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            BonusesRelationManager::class,
            ReviewsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSlots::route('/'),
            'create' => CreateSlot::route('/create'),
            'edit' => EditSlot::route('/{record}/edit'),
        ];
    }
}
