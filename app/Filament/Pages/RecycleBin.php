<?php

namespace App\Filament\Pages;

use App\Support\Trash;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class RecycleBin extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrash;

    protected static ?string $navigationLabel = 'Корзина';

    protected static ?string $title = 'Корзина';

    protected static string|UnitEnum|null $navigationGroup = 'Система';

    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.pages.recycle-bin';

    public string $trashType = 'slots';

    public static function getNavigationBadge(): ?string
    {
        $count = Trash::totalCount();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public function mount(): void
    {
        $types = array_keys(Trash::models());
        if (! in_array($this->trashType, $types, true)) {
            $this->trashType = $types[0] ?? 'slots';
        }
    }

    public function updatedTrashType(): void
    {
        $this->resetTable();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('purgeNow')
                ->label('Очистить старше '.Trash::retentionDays().' дн.')
                ->icon('heroicon-o-fire')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Очистить корзину')
                ->modalDescription('Окончательно удалит все записи старше '.Trash::retentionDays().' дней. Это нельзя отменить.')
                ->action(function (): void {
                    \Illuminate\Support\Facades\Artisan::call('trash:purge');
                    $this->resetTable();
                    Notification::make()
                        ->title('Старые записи из корзины удалены')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Удалённые записи · хранятся '.Trash::retentionDays().' дней')
            ->description('Удаление в админке сначала кладёт запись сюда. Восстановление возвращает на сайт / в каталог.')
            ->query($this->trashQuery())
            ->defaultSort('deleted_at', 'desc')
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('label')
                    ->label('Название')
                    ->getStateUsing(fn (Model $record): string => Trash::labelFor($record))
                    ->wrap()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $like = '%'.$search.'%';

                        return match ($this->trashType) {
                            'newsletter' => $query->where('email', 'ilike', $like),
                            'bonuses' => $query->where(function (Builder $q) use ($like): void {
                                $q->where('casino_name', 'ilike', $like)->orWhere('slug', 'ilike', $like);
                            }),
                            'game_promos' => $query->where('casino_name', 'ilike', $like),
                            'countries' => $query->where(function (Builder $q) use ($like): void {
                                $q->where('code', 'ilike', $like)->orWhere('name', 'ilike', $like);
                            }),
                            'reviews', 'post_comments' => $query->where('body', 'ilike', $like),
                            default => $query->where(function (Builder $q) use ($like, $search): void {
                                $q->where('slug', 'ilike', $like);
                                if (ctype_digit($search)) {
                                    $q->orWhere('id', (int) $search);
                                }
                            }),
                        };
                    }),
                TextColumn::make('deleted_at')
                    ->label('Удалён')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Удалится')
                    ->getStateUsing(function (Model $record): string {
                        /** @var \Illuminate\Support\Carbon|null $deleted */
                        $deleted = $record->deleted_at;
                        if (! $deleted) {
                            return '—';
                        }

                        $expires = $deleted->copy()->addDays(Trash::retentionDays());
                        $daysLeft = (int) now()->startOfDay()->diffInDays($expires->copy()->startOfDay(), false);

                        if ($daysLeft < 0) {
                            return 'скоро (просрочено)';
                        }

                        return $expires->toDateString().' · осталось '.$daysLeft.' дн.';
                    }),
            ])
            ->headerActions([
                Action::make('switchType')
                    ->label('Тип контента')
                    ->icon('heroicon-o-funnel')
                    ->form([
                        Select::make('trashType')
                            ->label('Показать')
                            ->options(Trash::labels())
                            ->default($this->trashType)
                            ->required(),
                    ])
                    ->fillForm(fn (): array => ['trashType' => $this->trashType])
                    ->action(function (array $data): void {
                        $this->trashType = (string) $data['trashType'];
                        $this->resetTable();
                    }),
            ])
            ->recordActions([
                Action::make('restore')
                    ->label('Восстановить')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Model $record): void {
                        $record->restore();
                        Notification::make()->title('Восстановлено')->success()->send();
                    }),
                Action::make('forceDelete')
                    ->label('Удалить навсегда')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Удалить навсегда')
                    ->modalDescription('Запись будет удалена безвозвратно.')
                    ->action(function (Model $record): void {
                        $record->forceDelete();
                        Notification::make()->title('Удалено навсегда')->success()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('restore')
                        ->label('Восстановить выбранные')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                $record->restore();
                            }
                            Notification::make()->title('Восстановлено: '.$records->count())->success()->send();
                        }),
                    BulkAction::make('forceDelete')
                        ->label('Удалить навсегда')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                $record->forceDelete();
                            }
                            Notification::make()->title('Удалено навсегда: '.$records->count())->success()->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('Корзина пуста')
            ->emptyStateDescription('Удалённые слоты, бонусы и другой контент появятся здесь.');
    }

    protected function trashQuery(): Builder
    {
        $models = Trash::models();
        $class = $models[$this->trashType] ?? reset($models);

        return $class::query()->onlyTrashed();
    }
}
