<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Surel berisi kode verifikasi 6 digit untuk pemulihan kata sandi
 * (Task 3.11, `rules.md` 14b poin 7).
 *
 * Sengaja mengirim ANGKA yang diketik, bukan tautan sekali klik: kode dapat
 * dibaca di satu perangkat lalu diketik di perangkat lain, dan tetap berguna
 * ketika jaringan lokus gagal memuat tautan panjang.
 */
class KodePemulihanSandiMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $kode  Kode 6 digit (bukan sidiknya)
     * @param  int  $menitBerlaku  Masa berlaku kode dalam menit
     */
    public function __construct(
        public string $kode,
        public int $menitBerlaku = 15,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Kode Pemulihan Kata Sandi SIM Transmigrasi');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.kode-pemulihan-sandi');
    }
}
