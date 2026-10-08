<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MagicLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $url,
        public string $intent = 'login',
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->intent === 'signup'
            ? 'Confirm your slots.tube registration'
            : 'Your slots.tube login link';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.magic-link',
            with: [
                'url' => $this->url,
                'user' => $this->user,
                'intent' => $this->intent,
            ],
        );
    }
}
