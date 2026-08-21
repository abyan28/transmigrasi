<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;
use App\Support\DummyData;

/**
 * Bidang yang menentukan dinas mana yang menangani sebuah pengaduan.
 *
 * SATU LAPORAN DITANGANI SATU DINAS. Keadaan lapangan di Kobalima Timur
 * menegaskan hal itu, sehingga alur status tetap tunggal dan tidak dipecah
 * per bidang (agents/rules.md bagian 10b poin 7).
 */
enum BidangPengaduan: string
{
    use PunyaLabel;

    case Ketransmigrasian = 'Ketransmigrasian';
    case Pertanian = 'Pertanian';

    /**
     * Menyimpulkan bidang penanganan dari kategori pengaduan.
     *
     * Petanya DIBACA DARI DATA MASTER, bukan dari `match` di dalam kode.
     * Sebelumnya `match` itu memuat dua belas kategori tanpa `default`,
     * sehingga kategori baru yang ditambahkan Admin lewat data master akan
     * melempar `UnhandledMatchError` begitu ada yang memilihnya, dan form
     * pengaduan mati total. Itulah sebabnya kategori tidak dapat menjadi data
     * master selama peta ini masih berupa `match`.
     *
     * Mengembalikan NULL untuk kategori yang bidangnya tidak dapat disimpulkan.
     * Kategori semacam itu menyatakan pokok masalah yang dapat jatuh ke dua
     * dinas sekaligus: sengketa lahan usaha bisa menyangkut pembagian lahan
     * (Ketransmigrasian) maupun produktivitasnya (Pertanian), sedangkan
     * bencana dan "lainnya" memang tidak menunjuk urusan tertentu.
     *
     * Nilai NULL berarti bidangnya WAJIB ditetapkan Admin atau Dinas
     * Transmigrasi sebelum status maju ke Diproses (10b poin 7b). Menebak
     * bidang untuk kategori semacam itu justru menyesatkan, sebab laporan akan
     * masuk ke daftar dinas yang keliru dan tertahan di sana.
     *
     * Nilai yang disimpulkan di sini hanya nilai AWAL; petugas selalu dapat
     * menimpanya (10b poin 7c).
     *
     * @param  string  $kategori  Kategori yang dipilih pelapor
     * @return self|null Bidang penanganan, atau null bila perlu ditetapkan petugas
     */
    public static function dariKategori(string $kategori): ?self
    {
        $bidang = DummyData::petaBidangKategori()[$kategori] ?? '';

        return $bidang === '' ? null : self::tryFrom($bidang);
    }

    /**
     * Memeriksa apakah bidang sebuah kategori perlu ditetapkan petugas.
     *
     * @param  string  $kategori  Kategori yang diperiksa
     * @return bool True bila kategori bersifat netral
     */
    public static function perluDitetapkan(string $kategori): bool
    {
        return self::dariKategori($kategori) === null;
    }

    /**
     * Peta kategori ke bidang bawaannya, untuk dibaca antarmuka.
     *
     * Kategori netral bernilai string kosong agar Alpine dapat membedakannya
     * dari bidang yang sudah pasti tanpa memerlukan pemeriksaan null.
     *
     * @return array<string, string> Peta nilai kategori ke nilai bidang
     */
    public static function petaDariKategori(): array
    {
        return DummyData::petaBidangKategori();
    }
}
