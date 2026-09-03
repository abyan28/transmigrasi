<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Surel berisi kata sandi sementara akun petugas (`rules.md` 14b poin 3a).
 *
 * Dipakai dua keadaan: akun baru dibuat Admin, dan kata sandi disetel ulang
 * Admin. Keduanya menghasilkan kata sandi SEMENTARA yang wajib diganti saat
 * masuk pertama. Surel adalah SALINAN, bukan pengganti penyerahan langsung --
 * jaringan lokus tidak selalu memadai.
 */
class KredensialAkunMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $nama  Nama petugas
     * @param  string  $email  Alamat surel yang menjadi kredensial masuk
     * @param  string  $sandiSementara  Kata sandi sementara (bukan hash)
     * @param  bool  $akunBaru  true = akun baru dibuat; false = kata sandi disetel ulang
     */
    public function __construct(
        public string $nama,
        public string $email,
        public string $sandiSementara,
        public bool $akunBaru = true,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->akunBaru
            ? 'Akun SIM Transmigrasi Anda telah dibuat'
            : 'Kata sandi SIM Transmigrasi Anda telah disetel ulang');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.kredensial-akun');
    }
}
