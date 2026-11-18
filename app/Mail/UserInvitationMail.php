<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $email,
        public string $login,
        public string $inviteUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('noreply@casini.ru', 'Касини'),
            subject: 'Доступ в Касини',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.user-invitation',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
