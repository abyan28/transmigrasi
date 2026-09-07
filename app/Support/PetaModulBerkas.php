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
use Illuminate\Support\Facades\DB;

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
    private const MODUL_IZIN = [
        'satuan_permukiman' => 'sp',
        'panen' => 'hasil_panen',
    ];

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

        if ($modul === 'satuan_permukiman') {
            return SatuanPermukiman::query()->terlihatOlehPengguna()->whereKey($id)->exists();
        }

        return $model::query()->whereKey($id)->exists();
    }

    public static function distribusiAlsintanTerlihat(int $alsintanId, int $berkasId): bool
    {
        return DB::table('alsintan_distribusi')
            ->where('alsintan_id', $alsintanId)
            ->where('foto_berkas_id', $berkasId)
            ->whereIn('poktan_id', Poktan::query()->select('id_poktan'))
            ->exists();
    }

    public static function modulIzin(string $modul): string
    {
        return self::MODUL_IZIN[$modul] ?? $modul;
    }

    public static function berkasMilik(string $modul, int $id, int $berkasId): bool
    {
        return match ($modul) {
            'transmigran' => self::adaPivot('transmigran_berkas', 'transmigran_id', $id, $berkasId),
            'rumah' => self::adaPivot('rumah_berkas', 'rumah_id', $id, $berkasId),
            'kawasan', 'kawasan_transmigrasi' => self::adaPivot('kawasan_transmigrasi_berkas', 'kawasan_transmigrasi_id', $id, $berkasId),
            'inventaris_sp' => self::adaPivot('inventaris_sp_berkas', 'inventaris_sp_id', $id, $berkasId),
            'fasilitas_sp' => self::adaPivot('fasilitas_sp_berkas', 'fasilitas_sp_id', $id, $berkasId),
            'infrastruktur' => self::adaPivot('infrastruktur_berkas', 'infrastruktur_id', $id, $berkasId),
            'alsintan' => self::adaPivot('alsintan_berkas', 'alsintan_id', $id, $berkasId)
                || self::distribusiAlsintanTerlihat($id, $berkasId),
            'penanaman' => self::adaPivot('penanaman_berkas', 'penanaman_id', $id, $berkasId),
            'panen', 'hasil_panen' => self::adaPivot('hasil_panen_berkas', 'hasil_panen_id', $id, $berkasId),
            'pengaduan' => self::adaPivot('pengaduan_berkas', 'pengaduan_id', $id, $berkasId)
                || DB::table('penanganan_pengaduan_berkas as pb')
                    ->join('penanganan_pengaduan as p', 'p.id_penanganan_pengaduan', '=', 'pb.penanganan_pengaduan_id')
                    ->where('p.pengaduan_id', $id)->where('pb.berkas_id', $berkasId)->exists(),
            'poktan' => DB::table('poktan')->where('id_poktan', $id)->where('berkas_id', $berkasId)->exists(),
            'saprotan' => DB::table('saprotan')->where('id_saprotan', $id)
                ->where(fn ($q) => $q->where('berkas_id', $berkasId)->orWhere('foto_berkas_id', $berkasId))->exists(),
            'satuan_permukiman' => DB::table('satuan_permukiman')->where('id_satuan_permukiman', $id)->where('berkas_id', $berkasId)->exists(),
            default => false,
        };
    }

    private static function adaPivot(string $tabel, string $kolomPemilik, int $id, int $berkasId): bool
    {
        return DB::table($tabel)->where($kolomPemilik, $id)->where('berkas_id', $berkasId)->exists();
    }
}
