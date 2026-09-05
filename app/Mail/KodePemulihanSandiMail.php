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

/**
 * Surel berisi kode verifikasi 6 digit untuk pemulihan kata sandi
 * (Task 3.11, `rules.md` 14b poin 7).
 *
 * Sengaja mengirim ANGKA yang diketik, bukan tautan sekali klik: kode dapat
 * dibaca di satu perangkat lalu diketik di perangkat lain, dan tetap berguna
 * ketika jaringan lokus gagal memuat tautan panjang.
 *
 * `ShouldQueue`: dikirim lewat `->queue()` supaya kanal pemulihan sandi tak
 * menahan permintaan HTTP. `ShouldBeEncrypted`: payload antrean membawa
 * `$kode` mentah -- tanpa ini kode pemulihan tersimpan terbaca di tabel
 * `jobs` selama masa berlakunya.
 */
class KodePemulihanSandiMail extends Mailable implements ShouldBeEncrypted, ShouldQueue
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
        return new Envelope(subject: 'Kode Pemulihan Kata Sandi '.KontenSistem::namaAplikasi());
    }

    public function content(): Content
    {
        return new Content(view: 'emails.kode-pemulihan-sandi');
    }
}
