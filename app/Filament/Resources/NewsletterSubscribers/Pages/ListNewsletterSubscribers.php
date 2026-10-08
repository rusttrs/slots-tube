<?php

namespace App\Filament\Resources\NewsletterSubscribers\Pages;

use App\Filament\Resources\NewsletterSubscribers\NewsletterSubscriberResource;
use App\Models\NewsletterSubscriber;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListNewsletterSubscribers extends ListRecords
{
    protected static string $resource = NewsletterSubscriberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Экспорт CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn (): StreamedResponse => $this->exportCsv()),
        ];
    }

    protected function exportCsv(): StreamedResponse
    {
        /** @var Builder $query */
        $query = $this->getFilteredTableQuery()->reorder()->orderBy('id');

        $filename = 'newsletter-subscribers-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            // Excel-friendly UTF-8
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['id', 'email', 'locale', 'status', 'subscribed_at', 'unsubscribed_at'], ';');

            $query->cursor()->each(function (NewsletterSubscriber $subscriber) use ($out): void {
                fputcsv($out, [
                    $subscriber->id,
                    $subscriber->email,
                    $subscriber->locale,
                    $subscriber->isActive() ? 'subscribed' : 'unsubscribed',
                    optional($subscriber->created_at)?->toDateTimeString(),
                    optional($subscriber->unsubscribed_at)?->toDateTimeString(),
                ], ';');
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
