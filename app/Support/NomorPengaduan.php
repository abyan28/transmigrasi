<?php

namespace App\Support;

use App\Models\Pengaduan;
use Illuminate\Support\Str;

/**
 * Nomor pengaduan publik (Task 8.7).
 *
 * Format `PGD-{TAHUN}-{URUT}-{ACAK}` -- mis. `PGD-2026-0001-K7F2M9`.
 *
 * - **URUT** empat digit, berjalan per tahun, dipertahankan sebab tetap
 *   berguna bagi petugas untuk menyebut dan mengurutkan laporan (`rules.md` 4c).
 * - **ACAK** enam karakter huruf besar + angka, SELALU ditambahkan sistem dan
 *   tidak dapat dimatikan lewat CMS (`rules.md` 4a). Halaman lacak terbuka
 *   tanpa login, jadi nomor berurutan yang dapat ditebak adalah permukaan
 *   serangan yang nyata; bagian acaklah yang menutup penyusuran.
 *
 * Awalan dan pola URUT kelak dapat diatur dinas lewat Pengelolaan Konten
 * (Task 9.6); bagian acak tetap di luar kendali itu.
 */
class NomorPengaduan
{
    /** Tanpa huruf/angka yang mudah tertukar saat dibacakan (0/O, 1/I). */
    private const ABJAD_ACAK = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public static function buat(?int $tahun = null): string
    {
        $tahun ??= (int) date('Y');
        // Awalan dapat diatur dinas lewat Pengelolaan Konten (Task 9.6);
        // bagian acak SELALU ditambahkan sistem (`rules.md` 4a).
        $awalan = KontenSistem::awalanNomorPengaduan();
        $urut = self::urutBerikutnya($awalan, $tahun);

        // Tabrakan bagian acak nyaris mustahil (~10^9 ruang), tetapi kolom
        // `nomor_pengaduan` UNIQUE -- diulang bila toh bertabrakan.
        do {
            $nomor = sprintf('%s-%d-%04d-%s', $awalan, $tahun, $urut, self::bagianAcak());
        } while (Pengaduan::withTrashed()->where('nomor_pengaduan', $nomor)->exists());

        return $nomor;
    }

    /**
     * Nomor urut berikutnya untuk satu tahun: satu lebih dari urut tertinggi
     * yang sudah dipakai tahun itu (bukan sekadar cacah baris -- baris yang
     * dihapus tetap memesan nomornya).
     */
    private static function urutBerikutnya(string $awalan, int $tahun): int
    {
        $awalanTahun = $awalan.'-'.$tahun.'-';

        $tertinggi = Pengaduan::withTrashed()
            ->where('nomor_pengaduan', 'like', $awalanTahun.'%')
            ->get(['nomor_pengaduan'])
            ->map(fn ($p) => (int) (explode('-', (string) $p->nomor_pengaduan)[2] ?? 0))
            ->max();

        return (int) $tertinggi + 1;
    }

    private static function bagianAcak(): string
    {
        $abjad = self::ABJAD_ACAK;
        $panjang = strlen($abjad);

        return collect(range(1, 6))
            ->map(fn () => $abjad[random_int(0, $panjang - 1)])
            ->implode('');
    }

    /**
     * Membaca komponen sebuah nomor. Dipakai halaman lacak yang menerima nomor
     * dari warga -- format bebas huruf besar/kecil dan spasi.
     *
     * @return array{awalan: string, tahun: int, urut: int, acak: string}|null
     */
    public static function urai(string $nomor): ?array
    {
        $nomor = Str::upper(trim($nomor));

        if (preg_match('/^([A-Z]+)-(\d{4})-(\d{1,6})-([A-Z0-9]{4,10})$/', $nomor, $c) !== 1) {
            return null;
        }

        return [
            'awalan' => $c[1],
            'tahun' => (int) $c[2],
            'urut' => (int) $c[3],
            'acak' => $c[4],
        ];
    }
}
