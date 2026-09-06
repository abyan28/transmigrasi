<?php

namespace App\Mail;

use App\Support\KontenSistem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountChangeNoticeMail extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $noticeText,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Pemberitahuan Perubahan Akun '.KontenSistem::namaAplikasi());
    }

    public function content(): Content
    {
        return new Content(view: 'emails.account-change-notice');
    }
}
