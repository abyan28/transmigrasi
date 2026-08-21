<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;
use App\Enums\Concerns\PunyaWarnaBadge;
use App\Support\DummyData;

/**
 * Status kesiapan layanan dasar sebuah satuan permukiman.
 *
 * PENTING: status ini menilai INFRASTRUKTUR DAN FASILITAS, bukan warganya.
 * Istilah bernada merendahkan seperti "terbelakang" atau "tertinggal"
 * DILARANG dipakai, sebab yang dinilai adalah jalan dan listrik, hal yang
 * justru berada di luar kendali penghuni (agents/rules.md bagian 10c.1).
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.30.
 */
enum StatusKondisiSp: string
{
    use PunyaLabel;
    use PunyaWarnaBadge;

    case Mandiri = 'Mandiri';
    case Berkembang = 'Berkembang';
    case PerluPenanganan = 'Perlu Penanganan';

    public function warna(): string
    {
        return match ($this) {
            self::Mandiri => 'success',
            self::Berkembang => 'warning',
            self::PerluPenanganan => 'error',
        };
    }

    /**
     * Penjelasan status, ditulis agar terbaca sebagai keadaan layanan
     * bukan sebagai penilaian atas penghuninya.
     *
     * @return string Keterangan berbahasa Indonesia
     */
    public function keterangan(): string
    {
        return DummyData::statusKondisiSpDari($this->value)['keterangan']
            ?? $this->bawaanKeterangan();
    }

    /**
     * Keterangan bawaan, dipakai bila datanya belum ada.
     *
     * Cadangan ini sengaja ada: status tanpa keterangan membuat kartu rekap
     * dashboard tampil sebagai judul tanpa penjelasan, dan label tanpa
     * rincian berhenti sebagai stempel (rules.md 10c.1 poin 4).
     *
     * @return string Keterangan berbahasa Indonesia
     */
    private function bawaanKeterangan(): string
    {
        return match ($this) {
            self::Mandiri => 'Seluruh layanan dasar tersedia dan berfungsi baik',
            self::Berkembang => 'Sebagian layanan tersedia, ada yang perlu diperbaiki',
            self::PerluPenanganan => 'Ada layanan dasar yang belum tersedia atau tidak berfungsi',
        };
    }

    /**
     * Nama status yang tampil, dapat disunting dinas.
     *
     * Nilai enum tetap dipakai sebagai kunci di dalam sistem, sedangkan yang
     * tampil di layar dibaca dari data. Dinas kerap punya istilah sendiri;
     * "Perlu Penanganan" pada satu kabupaten bisa disebut "Prioritas
     * Pembinaan" di kabupaten lain, dan keduanya menunjuk keadaan yang sama.
     *
     * @return string Nama yang tampil
     */
    public function label(): string
    {
        return DummyData::statusKondisiSpDari($this->value)['nama'] ?? $this->value;
    }

    /**
     * Menyimpulkan status dari skor dan keadaan parameter primer.
     *
     * ATURAN PRIMER NOL: satu saja parameter primer yang tidak tersedia
     * membuat SP berstatus Perlu Penanganan, berapa pun skor totalnya.
     * Tanpa aturan ini, SP tanpa air bersih tetapi lengkap fasilitas
     * penunjangnya dapat mencapai skor tinggi, dan angka itu menyesatkan
     * (agents/rules.md bagian 10c.4 poin 11).
     *
     * @param  float  $skor  Skor akhir 0 sampai 100
     * @param  bool  $adaPrimerNol  True bila ada parameter primer bernilai nol
     * @return self Status yang berlaku
     */
    public static function dariSkor(float $skor, bool $adaPrimerNol): self
    {
        if ($adaPrimerNol) {
            return self::PerluPenanganan;
        }

        // AMBANG DIBACA DARI DATA, bukan angka di dalam kode. Bobot parameter
        // sudah lebih dulu dapat disunting, sedangkan ambangnya terkunci,
        // padahal keduanya sama-sama keputusan kebijakan yang wajib divalidasi
        // dinas (rules.md 10c poin 13).
        //
        // Dibaca menurun dari yang tertinggi, dan urutan itu dijamin kolom
        // `urutan`. Status terendah berambang 0 sehingga selalu tercapai; tanpa
        // itu ada skor yang tidak mendapat status sama sekali.
        $ambang = DummyData::statusKondisiSp();

        usort($ambang, fn ($a, $b) => $b['ambang_bawah'] <=> $a['ambang_bawah']);

        foreach ($ambang as $baris) {
            if ($skor >= $baris['ambang_bawah']) {
                return self::from($baris['kode']);
            }
        }

        return self::PerluPenanganan;
    }
}
