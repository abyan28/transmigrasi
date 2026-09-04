<?php

namespace App\Support;

use App\Models\Alsintan;
use App\Models\FasilitasSp;
use App\Models\HasilPanen;
use App\Models\Infrastruktur;
use App\Models\InventarisSp;
use App\Models\KawasanTransmigrasi;
use App\Models\Lahan;
use App\Models\Penanaman;
use App\Models\PenangananPengaduan;
use App\Models\Pengaduan;
use App\Models\Poktan;
use App\Models\Rumah;
use App\Models\Saprotan;
use App\Models\SatuanPermukiman;
use App\Models\Transmigran;
use Illuminate\Database\Eloquent\Model;

/**
 * Peta nama modul berkas -> model pemiliknya (Task 10.6).
 *
 * `DokumenController` menerima `modul` sebagai parameter rute lalu mengalirkan
 * berkas dari disk privat. Sebelum berkas dikirim, baris pemiliknya diambil
 * lewat model di sini SEHINGGA GLOBAL SCOPE CAKUPAN DATA IKUT BERLAKU: operator
 * Per SP yang meminta berkas milik SP di luar penugasannya menerima 404, sama
 * seperti bila barisnya memang tidak ada (`rules.md` 5.0b + 14a poin 6).
 *
 * Modul referensi (kawasan, satuan permukiman) tidak disaring cakupan
 * (`CakupanDataSp` mengecualikannya) -- pemeriksaan `izin:lihat` sudah cukup.
 */
class PetaModulBerkas
{
    /**
     * @var array<string, class-string<Model>>
     */
    private const PETA = [
        'transmigran' => Transmigran::class,
        'rumah' => Rumah::class,
        'lahan' => Lahan::class,
        'poktan' => Poktan::class,
        'penanaman' => Penanaman::class,
        'panen' => HasilPanen::class,
        'hasil_panen' => HasilPanen::class,
        'pengaduan' => Pengaduan::class,
        'penanganan_pengaduan' => PenangananPengaduan::class,
        'infrastruktur' => Infrastruktur::class,
        'inventaris_sp' => InventarisSp::class,
        'fasilitas_sp' => FasilitasSp::class,
        // Induk alsintan/saprotan TIDAK disaring cakupan (deskripsi benda),
        // tetapi tetap dipetakan agar id yang tidak ada menghasilkan 404.
        'alsintan' => Alsintan::class,
        'saprotan' => Saprotan::class,
        // Data referensi -- tanpa scope cakupan.
        'kawasan' => KawasanTransmigrasi::class,
        'kawasan_transmigrasi' => KawasanTransmigrasi::class,
        'satuan_permukiman' => SatuanPermukiman::class,
    ];

    /**
     * True bila baris pemilik berkas terlihat oleh pengguna saat ini (global
     * scope cakupan data diterapkan otomatis). Modul tak dikenal -> true:
     * pemeriksaan `izin` pada controller yang menjadi penjaganya.
     */
    public static function pemilikTerlihat(string $modul, int $id): bool
    {
        $model = self::PETA[$modul] ?? null;

        if ($model === null) {
            return true;
        }

        return $model::query()->whereKey($id)->exists();
    }
}
