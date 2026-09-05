<?php

namespace App\Support;

use App\Mail\PengaduanMail;
use App\Models\Pengaduan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SurelPengaduan
{
    public static function kirim(Pengaduan $pengaduan, bool $baru = false): bool
    {
        if (! $pengaduan->email_pelapor) {
            return false;
        }

        try {
            // Diantre (bukan `->send()` langsung): kanal pengaduan publik
            // tanpa login tidak boleh menunggu SMTP yang lambat/tak terjangkau.
            Mail::to($pengaduan->email_pelapor)->queue(new PengaduanMail($pengaduan, $baru));

            return true;
        } catch (Throwable $e) {
            Log::error('Surel pengaduan gagal diantre.', [
                'pengaduan_id' => $pengaduan->id_pengaduan,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
