<?php

namespace Database\Seeders;

use App\Models\Berkas;
use App\Models\Desa;
use App\Models\KawasanTransmigrasi;
use App\Models\RuteAksesibilitasSp;
use App\Models\SatuanPermukiman;
use App\Support\DummyData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
        DB::transaction(function () {
            $desa = Desa::pluck('id_desa', 'nama');
            $kawasan = KawasanTransmigrasi::pluck('id_kawasan_transmigrasi', 'nama');
            $berkas = collect(DummyData::berkas())->keyBy('id_berkas');

            foreach (DummyData::satuanPermukiman() as $sp) {
                $desaId = $desa[$sp['desa']] ?? null;
                $kawasanId = $kawasan[$sp['kawasan']] ?? null;

                if ($desaId === null || $kawasanId === null) {
                    $this->command?->warn('Wilayah untuk SP '.$sp['nama'].' belum ada -- dilewati.');

                    continue;
                }

                if (! empty($sp['berkas_id']) && $berkas->has($sp['berkas_id'])) {
                    $dokumen = $berkas[$sp['berkas_id']];
                    Berkas::updateOrCreate(
                        ['id_berkas' => $dokumen['id_berkas']],
                        collect($dokumen)->except(['id_berkas', 'user_id'])->all() + ['user_id' => null],
                    );
                }

                SatuanPermukiman::updateOrCreate(
                    ['id_satuan_permukiman' => $sp['id_satuan_permukiman']],
                    collect($sp)
                        ->only((new SatuanPermukiman)->getFillable())
                        ->merge([
                            'kawasan_id' => $kawasanId,
                            'desa_id' => $desaId,
                            'berkas_id' => $sp['berkas_id'] ?? null,
                        ])
                        ->all(),
                );
            }

            foreach (DummyData::ruteAksesibilitasSp() as $rute) {
                RuteAksesibilitasSp::updateOrCreate(
                    ['id_rute_aksesibilitas_sp' => $rute['id_rute_aksesibilitas_sp']],
                    collect($rute)->except('id_rute_aksesibilitas_sp')->all(),
                );
            }
        });
    }
}
