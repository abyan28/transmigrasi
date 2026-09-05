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
 * Surel berisi kata sandi sementara akun petugas (`rules.md` 14b poin 3a).
 *
 * Dipakai dua keadaan: akun baru dibuat Admin, dan kata sandi disetel ulang
 * Admin. Keduanya menghasilkan kata sandi SEMENTARA yang wajib diganti saat
 * masuk pertama. Surel adalah SALINAN, bukan pengganti penyerahan langsung --
 * jaringan lokus tidak selalu memadai.
 *
 * `ShouldQueue`: dikirim lewat `->queue()`, bukan `->send()` langsung, supaya
 * gangguan SMTP tidak menahan permintaan HTTP pembuatan akun. `ShouldBeEncrypted`:
 * payload antrean membawa `$sandiSementara` MENTAH (bukan hash) -- tanpa ini
 * kata sandi sementara akan tersimpan terbaca di tabel `jobs` sampai pekerja
 * antrean memprosesnya, bertentangan dengan `rules.md` 14b poin 14.
 */
class KredensialAkunMail extends Mailable implements ShouldBeEncrypted, ShouldQueue
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
        // Subjek memakai nama LENGKAP: surel mendarat di kotak masuk pribadi
        // bersama ratusan pesan lain, sehingga akronim saja tidak cukup
        // dikenali penerima yang baru pertama kali menerimanya.
        return new Envelope(subject: $this->akunBaru
            ? 'Akun '.KontenSistem::namaAplikasi().' Anda telah dibuat'
            : 'Kata sandi '.KontenSistem::namaAplikasi().' Anda telah disetel ulang');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.kredensial-akun');
    }
}
