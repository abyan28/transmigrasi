<?php

namespace Database\Seeders;

use App\Models\FasilitasSp;
use App\Models\InventarisSp;
use App\Support\DummyData;
use Illuminate\Database\Seeder;

/**
 * Inventaris dan fasilitas SP (Task 4.3 + 4.4).
 *
 * Keduanya disatukan sebab strukturnya sama persis -- aset milik SP beserta
 * kondisi, sumber dana, dan status penyerahannya.
 *
 * Kolom label `satuan_permukiman` pada data contoh TIDAK ditanam: ia turunan
 * relasi, bukan kolom. `rincian_kondisi` berupa JSON histogram per jenis
 * barang, dan barisnya boleh tidak punya kunci itu sama sekali.
 *
 * `cakupan` fasilitas (pivot lintas SP) ikut ditanam: satu fasilitas dapat
 * melayani beberapa SP sekaligus, dan SP pangkalnya WAJIB ikut tercantum.
 */
class AsetSpSeeder extends Seeder
{
    public function run(): void
    {
        $this->tanamInventaris();
        $this->tanamFasilitas();
    }

    private function tanamInventaris(): void
    {
        foreach (DummyData::inventarisSp() as $i) {
            InventarisSp::updateOrCreate(
                ['id_inventaris_sp' => $i['id_inventaris_sp']],
                [
                    'satuan_permukiman_id' => $i['satuan_permukiman_id'],
                    'jenis_inventaris' => $i['jenis_inventaris'],
                    'nama_barang' => $i['nama_barang'],
                    'jumlah' => $i['jumlah'],
                    'satuan_barang' => $i['satuan_barang'],
                    'tahun_perolehan' => $i['tahun_perolehan'],
                    'sumber_dana' => $i['sumber_dana'],
                    'status_penyerahan' => $i['status_penyerahan'],
                    'kondisi' => $i['kondisi'],
                    'rincian_kondisi' => $i['rincian_kondisi'] ?? null,
                    'keterangan' => $i['keterangan'],
                ],
            );
        }
    }

    private function tanamFasilitas(): void
    {
        foreach (DummyData::fasilitasSp() as $f) {
            $fasilitas = FasilitasSp::updateOrCreate(
                ['id_fasilitas_sp' => $f['id_fasilitas_sp']],
                [
                    'satuan_permukiman_id' => $f['satuan_permukiman_id'],
                    'jenis_fasilitas' => $f['jenis_fasilitas'],
                    'nama_fasilitas' => $f['nama_fasilitas'],
                    'jumlah' => $f['jumlah'],
                    'tahun_perolehan' => $f['tahun_perolehan'],
                    'sumber_dana' => $f['sumber_dana'],
                    'status_penyerahan' => $f['status_penyerahan'],
                    'kondisi' => $f['kondisi'],
                    'rincian_kondisi' => $f['rincian_kondisi'] ?? null,
                    'lintang' => $f['lintang'] ?? null,
                    'bujur' => $f['bujur'] ?? null,
                    'keterangan' => $f['keterangan'],
                ],
            );

            // Cakupan lintas SP. SP pangkal WAJIB ikut tercantum, sebab
            // fasilitas yang tak melayani SP tempatnya berdiri tidak masuk akal.
            $fasilitas->cakupan()->sync($f['satuan_permukiman_ids'] ?? [$f['satuan_permukiman_id']]);
        }
    }
}
