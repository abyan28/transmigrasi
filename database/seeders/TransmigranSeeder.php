<?php

namespace Database\Seeders;

use App\Models\AnggotaKeluarga;
use App\Models\RiwayatKepalaKeluarga;
use App\Models\Transmigran;
use App\Support\DummyData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Transmigran + anggota keluarga (Task 5.1).
 *
 * Baris dibuat dari `DummyData::transmigran()` / `DummyData::anggotaKeluarga()`
 * dengan `id_transmigran` / `id_anggota_keluarga` DIPAKSA SAMA seperti data
 * contoh, sehingga setiap rujukan id yang sudah ada (tautan rincian, filter,
 * data poktan/lahan yang masih `DummyData`) tetap menunjuk baris yang sama.
 *
 * `uuid` ditulis sekali saat baris dibuat lalu dipertahankan pada penanaman
 * ulang: ia pengenal publik URL dan tidak boleh berganti diam-diam.
 *
 * `jumlah_anggota_keluarga` dan `usia` BUKAN kolom (diturunkan); tidak ditanam.
 * `status_sertifikat` ditanam apa adanya walau isiannya lewat form lahan
 * (`rules.md` 7.6a) -- kolomnya tetap milik `transmigran`.
 *
 * Sejak Task 5.2 juga menanam `riwayat_kepala_keluarga` (dua contoh suksesi)
 * supaya tab Riwayat Kepala Keluarga pada halaman rincian punya isi.
 */
class TransmigranSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DummyData::transmigran() as $t) {
            $baris = Transmigran::withTrashed()->firstOrNew(['id_transmigran' => $t['id_transmigran']]);
            $baris->uuid ??= (string) Str::uuid();
            $baris->fill([
                'satuan_permukiman_id' => $t['satuan_permukiman_id'],
                'nik' => $t['nik'],
                'no_kk' => $t['no_kk'],
                'nama_kepala_keluarga' => $t['nama_kepala_keluarga'],
                'jenis_kelamin' => $t['jenis_kelamin'],
                'agama' => $t['agama'],
                'tempat_lahir' => $t['tempat_lahir'],
                'tanggal_lahir' => $t['tanggal_lahir'],
                'pendidikan_terakhir' => $t['pendidikan_terakhir'],
                'pekerjaan_kepala_keluarga' => $t['pekerjaan_kepala_keluarga'],
                'pendapatan_per_bulan' => $t['pendapatan_per_bulan'] ?? null,
                'daerah_asal_kabupaten_id' => $t['daerah_asal_kabupaten_id'] ?? null,
                'tahun_kedatangan' => $t['tahun_kedatangan'],
                'status_tinggal' => $t['status_tinggal'],
                'status_anggota_poktan' => $t['status_anggota_poktan'],
                'status_sertifikat' => $t['status_sertifikat'],
                'telepon' => $t['telepon'] ?? null,
                'keterangan' => $t['keterangan'] ?? null,
            ]);
            $baris->save();
        }

        foreach (DummyData::anggotaKeluarga() as $a) {
            AnggotaKeluarga::withTrashed()->updateOrCreate(
                ['id_anggota_keluarga' => $a['id_anggota_keluarga']],
                [
                    'transmigran_id' => $a['transmigran_id'],
                    'hubungan' => $a['hubungan'],
                    'nama_lengkap' => $a['nama_lengkap'],
                    'nik' => $a['nik'] ?? null,
                    'jenis_kelamin' => $a['jenis_kelamin'] ?? null,
                    'tempat_lahir' => $a['tempat_lahir'] ?? null,
                    'tanggal_lahir' => $a['tanggal_lahir'] ?? null,
                    'agama' => $a['agama'] ?? null,
                    'kegiatan' => $a['kegiatan'] ?? null,
                    'pendidikan_terakhir' => $a['pendidikan_terakhir'] ?? null,
                    'pekerjaan' => $a['pekerjaan'] ?? null,
                    'pendapatan_per_bulan' => $a['pendapatan_per_bulan'] ?? null,
                    'telepon' => $a['telepon'] ?? null,
                    'keterangan' => $a['keterangan'] ?? null,
                    'status' => $a['status'],
                    'tanggal_peristiwa' => $a['tanggal_peristiwa'] ?? null,
                    'keterangan_peristiwa' => $a['keterangan_peristiwa'] ?? null,
                ],
            );
        }

        foreach (DummyData::riwayatKepalaKeluarga() as $r) {
            RiwayatKepalaKeluarga::updateOrCreate(
                ['id_riwayat_kepala_keluarga' => $r['id_riwayat_kepala_keluarga']],
                [
                    'transmigran_id' => $r['transmigran_id'],
                    'nik_lama' => $r['nik_lama'],
                    'nama_lama' => $r['nama_lama'],
                    'nik_baru' => $r['nik_baru'],
                    'nama_baru' => $r['nama_baru'],
                    'no_kk_lama' => $r['no_kk_lama'],
                    'no_kk_baru' => $r['no_kk_baru'],
                    'tanggal_pergantian' => $r['tanggal_pergantian'],
                    'alasan' => $r['alasan'],
                    'hubungan_pengganti' => $r['hubungan_pengganti'],
                    'keterangan' => $r['keterangan'] ?? null,
                ],
            );
        }
    }
}
