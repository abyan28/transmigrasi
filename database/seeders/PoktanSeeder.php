<?php

namespace Database\Seeders;

use App\Models\AnggotaPoktan;
use App\Models\Berkas;
use App\Models\Poktan;
use App\Support\DummyData;
use Illuminate\Database\Seeder;

/**
 * Kelompok tani + anggota poktan (Task 6.4 / 6.5).
 *
 * `id_poktan` / `id_anggota_poktan` dipaksa sama seperti data contoh. `slug`
 * diturunkan `BerslugOtomatis` saat penyimpanan. `berkas_id` (SK) disalin bila
 * berkasnya ada -> dijalankan SETELAH `BerkasSeeder`. FK ketua/anggota ->
 * `TransmigranSeeder` (transmigran + anggota_keluarga).
 *
 * `jumlah_anggota` dan luas lahan kelompok BUKAN kolom (diturunkan).
 */
class PoktanSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DummyData::poktan() as $p) {
            $poktan = Poktan::withTrashed()->firstOrNew(['id_poktan' => $p['id_poktan']]);
            $poktan->fill([
                'satuan_permukiman_id' => $p['satuan_permukiman_id'],
                'nama' => $p['nama'],
                'asal_ketua' => $p['asal_ketua'],
                'ketua_transmigran_id' => $p['ketua_transmigran_id'] ?? null,
                'ketua_anggota_keluarga_id' => $p['ketua_anggota_keluarga_id'] ?? null,
                // Pada jalur relasi, nama/nik ketua null (dibaca lewat relasi).
                'nama_ketua' => $p['asal_ketua'] === 'Bukan Transmigran' ? ($p['nama_ketua'] ?? null) : null,
                'nik_ketua' => $p['asal_ketua'] === 'Bukan Transmigran' ? ($p['nik_ketua'] ?? null) : null,
                'tahun_berdiri' => $p['tahun_berdiri'] ?? null,
                'telepon_ketua' => $p['telepon_ketua'] ?? null,
                'email_ketua' => $p['email_ketua'] ?? null,
                'alamat_ketua' => $p['alamat_ketua'] ?? null,
                'luas_kering_ketua' => $p['luas_kering_ketua'] ?? null,
                'luas_basah_ketua' => $p['luas_basah_ketua'] ?? null,
                'lintang' => $p['lintang'] ?? null,
                'bujur' => $p['bujur'] ?? null,
                'keterangan' => $p['keterangan'] ?? null,
            ]);
            $poktan->save();

            if (! empty($p['berkas_id']) && Berkas::whereKey($p['berkas_id'])->exists()) {
                $poktan->forceFill(['berkas_id' => $p['berkas_id']])->save();
            }
        }

        // `anggotaPoktan()` di-map dengan kunci tampilan tetapi kolom mentahnya
        // tetap di dalamnya, jadi cukup dibaca apa adanya.
        foreach (DummyData::anggotaPoktan() as $a) {
            AnggotaPoktan::withTrashed()->updateOrCreate(
                ['id_anggota_poktan' => $a['id_anggota_poktan']],
                [
                    'poktan_id' => $a['poktan_id'],
                    'transmigran_id' => $a['transmigran_id'],
                    'asal_wakil' => $a['asal_wakil'],
                    'anggota_keluarga_id' => $a['anggota_keluarga_id'] ?? null,
                    'telepon_wakil' => $a['telepon_wakil'] ?? null,
                    'jabatan' => $a['jabatan'],
                    'tanggal_masuk' => $a['tanggal_masuk'],
                    'status' => $a['status'],
                    'tanggal_keluar' => $a['tanggal_keluar'] ?? null,
                    'alasan_keluar' => $a['alasan_keluar'] ?? null,
                    'keterangan' => $a['keterangan'] ?? null,
                ],
            );
        }
    }
}
