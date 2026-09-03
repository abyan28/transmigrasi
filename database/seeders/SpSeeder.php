<?php

namespace Database\Seeders;

use App\Models\Desa;
use App\Models\SatuanPermukiman;
use App\Support\DummyData;
use Illuminate\Database\Seeder;

/**
 * Enam satuan permukiman lokus (Task 4.2).
 *
 * `desa_id` diturunkan dari NAMA desa pada data contoh, sebab `DummyData`
 * menyimpan labelnya saja. Aman di sini karena keenam nama desa lokus unik
 * dan sudah ditanam `WilayahSeeder`; nama yang tak ditemukan DILEWATI dengan
 * peringatan, bukan ditanam ber-`desa_id` karangan yang akan menautkan SP ke
 * wilayah yang keliru.
 *
 * `jumlah_kk_terisi` pada data contoh BUKAN kolom -- ia turunan dari cacah
 * transmigran, sehingga tidak ikut ditanam.
 *
 * `slug` diturunkan `Sluggable` (Task 3.9) saat penyimpanan.
 */
class SpSeeder extends Seeder
{
    public function run(): void
    {
        $desa = Desa::pluck('id_desa', 'nama');

        foreach (DummyData::satuanPermukiman() as $sp) {
            $desaId = $desa[$sp['desa']] ?? null;

            if ($desaId === null) {
                $this->command?->warn('Desa '.$sp['desa'].' belum ada -- SP '.$sp['nama'].' dilewati.');

                continue;
            }

            SatuanPermukiman::updateOrCreate(
                ['id_satuan_permukiman' => $sp['id_satuan_permukiman']],
                [
                    'kawasan_id' => 1,
                    'desa_id' => $desaId,
                    'nama' => $sp['nama'],
                    'kode_sp' => $sp['kode_sp'],
                    'tahun_penempatan' => $sp['tahun_penempatan'],
                    'luas_lahan' => $sp['luas_lahan'],
                    'jumlah_kk_rencana' => $sp['jumlah_kk_rencana'],
                    'lintang' => $sp['lintang'],
                    'bujur' => $sp['bujur'],
                    'keterangan' => $sp['keterangan'],
                ],
            );
        }
    }
}
