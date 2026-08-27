<?php

namespace App\Providers;

use App\Enums\JenisReferensi;
use App\Support\DummyData;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Penyuplai data rujukan untuk berkas form yang dipakai bersama.
 *
 * Halaman yang punya rute sendiri menerima datanya dari rute, sebab di situlah
 * kelak controller Tahap 4 mengambil alih. Berkas form tidak dapat mengikuti
 * pola itu: satu berkas form disisipkan dari INDUK YANG BERBEDA-BEDA, yakni
 * modal tambah dan modal ubah pada halaman daftar, serta modal ubah pada
 * halaman rincian. Menyalurkan opsinya lewat rute berarti tiga rute wajib
 * mengoper isian yang sama persis, dan satu saja yang terlewat menghasilkan
 * dropdown kosong tanpa galat apa pun.
 *
 * Composer menyelesaikannya dari sisi yang benar: opsinya melekat pada FORM,
 * bukan pada siapa yang menyisipkannya. Penyuplaian berjalan malas, hanya
 * ketika form itu benar-benar dirender.
 *
 * Saat Tahap 4 masuk, yang berubah hanya isi `nilaiRujukan()`, dari pembacaan
 * `DummyData` menjadi kueri. Tidak ada satu pun view maupun rute yang ikut
 * disunting.
 */
class ViewServiceProvider extends ServiceProvider
{
    /**
     * Rujukan yang dibutuhkan setiap berkas form.
     *
     * Ditulis mendatar dan lengkap, bukan dibangkitkan dari pola nama, supaya
     * `grep` atas satu nama kunci langsung menunjukkan siapa saja pemakainya.
     *
     * @var array<string, list<string>>
     */
    private const RUJUKAN_FORM = [
        'pages.alsintan.form' => ['daftarPoktan', 'opsiKondisi', 'opsiSumberDana'],
    ];

    public function boot(): void
    {
        $this->suplaiRujukanForm();
    }

    /**
     * Memasang composer untuk setiap berkas form yang terdaftar.
     */
    private function suplaiRujukanForm(): void
    {
        foreach (self::RUJUKAN_FORM as $berkas => $daftarKunci) {
            View::composer($berkas, function ($tampilan) use ($daftarKunci): void {
                foreach ($daftarKunci as $kunci) {
                    $tampilan->with($kunci, self::nilaiRujukan($kunci));
                }
            });
        }
    }

    /**
     * Satu-satunya tempat nama kunci diterjemahkan menjadi datanya.
     *
     * `opsiReferensi()` dan `opsiFilterReferensi()` sengaja TIDAK dipertukarkan.
     * Yang pertama hanya memuat nilai aktif sebab form menawarkan pilihan untuk
     * data baru; yang kedua ikut memuat nilai nonaktif sebab filter menyaring
     * data lama. Kunci berawalan `opsiFilter` karena itu milik halaman daftar,
     * dan tidak pernah muncul pada berkas form.
     */
    private static function nilaiRujukan(string $kunci): mixed
    {
        return match ($kunci) {
            'daftarPoktan' => DummyData::poktan(),
            'opsiKondisi' => DummyData::opsiReferensi(JenisReferensi::Kondisi),
            'opsiSumberDana' => DummyData::opsiReferensi(JenisReferensi::SumberDana),
            default => throw new \InvalidArgumentException("Kunci rujukan tidak dikenal: {$kunci}"),
        };
    }
}
