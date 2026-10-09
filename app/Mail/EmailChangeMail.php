<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailChangeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $newEmail,
        public string $url,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Confirm your new slots.tube email');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.email-change',
            with: [
                'url' => $this->url,
                'user' => $this->user,
                'newEmail' => $this->newEmail,
            ],
        );
    }
}
