<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyRegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $email,
        public string $verifyUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('noreply@casini.ru', 'Касини'),
            subject: 'Подтверждение регистрации — Касини',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.verify-registration',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
