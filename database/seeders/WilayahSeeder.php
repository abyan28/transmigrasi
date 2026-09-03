<?php

namespace Database\Seeders;

use App\Models\Desa;
use App\Models\Kabupaten;
use App\Models\Kecamatan;
use App\Models\Provinsi;
use App\Support\DataWilayah;
use Illuminate\Database\Seeder;

/**
 * Wilayah bertingkat: provinsi > kabupaten > kecamatan > desa (Task 4.1).
 *
 * Provinsi (38) dan kabupaten (514) ditanam LENGKAP se-Indonesia dari
 * `App\Support\DataWilayah`, sebab daftar ini melayani pemilihan daerah ASAL
 * transmigran yang dapat datang dari mana pun (`rules.md` 4a.9a).
 *
 * Kecamatan dan desa SENGAJA hanya wilayah lokus -- 4 kecamatan, 6 desa di
 * Kabupaten Malaka. Berkas sumber nasional memuat ~7.000 kecamatan dan ~83.000
 * desa; menanamnya penuh membuat dropdown pemilihan desa pada form SP berisi
 * puluhan ribu pilihan yang seluruhnya keliru kecuali enam. Pemuatan penuh
 * menunggu pengambilan bertahap lewat endpoint.
 *
 * **Id dipaksa sama dengan kode BPS** (Malaka = 5321, NTT = 53), bukan
 * auto-increment. Dengan begitu id di basis data sama persis dengan id yang
 * dipakai `DataWilayah` dan `DummyData`, sehingga peralihan tampilan ke
 * Eloquent tidak menggeser satu pun rujukan yang sudah ada.
 *
 * Idempoten: `updateOrCreate` pada kunci utama, aman dijalankan ulang.
 */
class WilayahSeeder extends Seeder
{
    /**
     * Kecamatan lokus. Id kecil (1-4) mengikuti `DummyData::wilayah()`; kode
     * BPS-nya belum dipastikan sehingga dibiarkan NULL, bukan dikarang
     * (`rules.md` 19a: data contoh bukan bukti tentang lapangan).
     *
     * @var list<array{id: int, nama: string}>
     */
    private const KECAMATAN = [
        ['id' => 1, 'nama' => 'Laen Manen'],
        ['id' => 2, 'nama' => 'Malaka Tengah'],
        ['id' => 3, 'nama' => 'Wewiku'],
        ['id' => 4, 'nama' => 'Rinhat'],
    ];

    /**
     * Enam desa lokus, masing-masing menaungi satu SP.
     *
     * @var list<array{id: int, kecamatan_id: int, nama: string}>
     */
    private const DESA = [
        ['id' => 1, 'kecamatan_id' => 1, 'nama' => 'Kapitan Meo'],
        ['id' => 2, 'kecamatan_id' => 1, 'nama' => 'Tniumanu'],
        ['id' => 3, 'kecamatan_id' => 2, 'nama' => 'Harekakae'],
        ['id' => 4, 'kecamatan_id' => 3, 'nama' => 'Weoe'],
        ['id' => 5, 'kecamatan_id' => 4, 'nama' => 'Naet'],
        ['id' => 6, 'kecamatan_id' => 4, 'nama' => 'Weain'],
    ];

    /** Kabupaten lokus (BPS 5321), induk seluruh kecamatan di atas. */
    private const KABUPATEN_LOKUS = 5321;

    public function run(): void
    {
        $this->tanamProvinsi();
        $this->tanamKabupaten();
        $this->tanamKecamatanDanDesa();
    }

    private function tanamProvinsi(): void
    {
        foreach (DataWilayah::provinsi() as $p) {
            Provinsi::updateOrCreate(
                ['id_provinsi' => $p['id']],
                ['nama' => $p['nama'], 'kode' => $p['kode']],
            );
        }
    }

    private function tanamKabupaten(): void
    {
        foreach (DataWilayah::kabupaten() as $k) {
            Kabupaten::updateOrCreate(
                ['id_kabupaten' => $k['id']],
                [
                    'provinsi_id' => $k['provinsi_id'],
                    'nama' => $k['nama'],
                    'kode' => $k['kode'],
                ],
            );
        }
    }

    private function tanamKecamatanDanDesa(): void
    {
        foreach (self::KECAMATAN as $k) {
            Kecamatan::updateOrCreate(
                ['id_kecamatan' => $k['id']],
                ['kabupaten_id' => self::KABUPATEN_LOKUS, 'nama' => $k['nama'], 'kode' => null],
            );
        }

        foreach (self::DESA as $d) {
            Desa::updateOrCreate(
                ['id_desa' => $d['id']],
                ['kecamatan_id' => $d['kecamatan_id'], 'nama' => $d['nama'], 'kode' => null],
            );
        }
    }
}
