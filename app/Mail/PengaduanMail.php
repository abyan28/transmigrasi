<?php

namespace App\Mail;

use App\Models\Pengaduan;
use App\Support\KontenSistem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * `ShouldQueue`: dikirim lewat `->queue()` (`App\Support\SurelPengaduan`),
 * bukan `->send()` langsung -- kanal pengaduan publik tanpa login tak boleh
 * menunggu SMTP yang lambat/tak terjangkau.
 */
class PengaduanMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Pengaduan $pengaduan,
        public bool $baru = false,
    ) {}

    public function envelope(): Envelope
    {
        $keterangan = $this->baru ? 'Pengaduan diterima' : 'Status pengaduan: '.$this->pengaduan->status->label();

        return new Envelope(subject: $keterangan.' - '.KontenSistem::namaAplikasi());
    }

    public function content(): Content
    {
        return new Content(view: 'emails.pengaduan');
    }
}
