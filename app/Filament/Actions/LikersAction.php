<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;

/**
 * Click on a "Лайки" cell → modal with who liked exactly this record.
 */
class LikersAction
{
    public static function make(string $heading): Action
    {
        return Action::make('likers')
            ->modalHeading($heading)
            ->modalContent(fn (Model $record) => view('filament.likes.likers', [
                'likes' => $record->likes()->with('user')->latest('id')->limit(200)->get(),
                'total' => (int) $record->likes_count,
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Закрыть')
            ->modalWidth('md');
    }
}
