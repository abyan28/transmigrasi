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
        'pages.saprotan.form' => ['daftarPoktan', 'daftarSatuan', 'daftarKomoditas', 'opsiSumberDana'],
        'pages.infrastruktur.form' => ['daftarSp', 'opsiJenisInfrastruktur', 'opsiSumberDana', 'opsiKondisi'],
        'pages.komoditas.form' => ['daftarSatuan', 'sebaran', 'opsiTipeKomoditas'],
        'pages.rumah.form' => ['transmigranTanpaRumah', 'daftarTransmigran', 'daftarSp', 'opsiKondisiRumah', 'opsiStatusHunian'],
        'pages.poktan.form' => ['daftarSp', 'daftarTransmigran', 'kontakTransmigran', 'lahanTransmigran'],
        'pages.poktan.form-anggota' => ['daftarTransmigran', 'kontakTransmigran', 'lahanTransmigran', 'opsiJabatanAnggota'],
        'pages.lahan.form' => ['daftarTransmigran', 'daftarSp', 'opsiJenisDokumenLahan'],
        'pages.transmigran.form' => ['daftarSp', 'saranPekerjaan'],
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
            'daftarSatuan' => DummyData::satuan(),
            'daftarKomoditas' => DummyData::komoditas(),
            'daftarSp' => DummyData::satuanPermukiman(),
            'daftarTransmigran' => DummyData::transmigran(),
            'transmigranTanpaRumah' => DummyData::transmigranTanpaRumah(),
            'sebaran' => DummyData::sebaranKomoditas(),

            // Hanya nama pekerjaannya yang dipakai, sebagai saran `<datalist>`.
            // Cacahnya tidak ikut, sebab isian ini bebas diketik.
            'saranPekerjaan' => array_keys(DummyData::sebaranPekerjaan()),

            'opsiKondisi' => DummyData::opsiReferensi(JenisReferensi::Kondisi),
            'opsiKondisiRumah' => DummyData::opsiReferensi(JenisReferensi::KondisiRumah),
            'opsiStatusHunian' => DummyData::opsiReferensi(JenisReferensi::StatusHunian),
            'opsiJenisInfrastruktur' => DummyData::opsiReferensi(JenisReferensi::JenisInfrastruktur),
            'opsiTipeKomoditas' => DummyData::opsiReferensi(JenisReferensi::TipeKomoditas),
            'opsiJenisDokumenLahan' => DummyData::opsiReferensi(JenisReferensi::JenisDokumenLahan),
            'opsiSumberDana' => DummyData::opsiReferensi(JenisReferensi::SumberDana),
            'opsiJabatanAnggota' => DummyData::opsiReferensi(JenisReferensi::JabatanAnggotaPoktan),
            'kontakTransmigran' => self::petaKeluarga()['kontak'],
            'lahanTransmigran' => self::petaKeluarga()['lahan'],
            default => throw new \InvalidArgumentException("Kunci rujukan tidak dikenal: {$kunci}"),
        };
    }

    /**
     * Peta id keluarga ke telepon dan rekap lahannya, dipakai di sisi klien
     * agar mengisi kontak serta luas ketua tidak menuntut permintaan tambahan
     * ke peladen hanya untuk membaca satu nomor.
     *
     * Disusun sekali lalu diingat, sebab `rekapLahanKeluarga()` menelusuri
     * seluruh bidang lahan untuk SETIAP keluarga. Dahulu perulangan ini
     * ditulis dua kali, di `poktan.form` dan `poktan.form-anggota`, dan
     * keduanya dapat muncul pada halaman yang sama sehingga penelusurannya
     * berjalan dua kali penuh.
     *
     * Ingatannya hidup selama satu permintaan. Saat Tahap 6 menggantinya
     * dengan kueri, yang tepat adalah satu kueri agregat, bukan pengingat ini.
     *
     * @return array{kontak: array<string, string>, lahan: array<string, mixed>}
     */
    private static function petaKeluarga(): array
    {
        static $peta = null;

        if ($peta !== null) {
            return $peta;
        }

        $kontak = [];
        $lahan = [];

        foreach (DummyData::transmigran() as $t) {
            $kunci = (string) $t['id_transmigran'];
            $kontak[$kunci] = $t['telepon'] ?? '';
            $lahan[$kunci] = DummyData::rekapLahanKeluarga($t['id_transmigran']);
        }

        return $peta = ['kontak' => $kontak, 'lahan' => $lahan];
    }
}
