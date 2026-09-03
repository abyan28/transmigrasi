<?php

namespace Database\Seeders;

use App\Models\Berkas;
use App\Support\DummyData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Registry `berkas` beserta pivot pemiliknya (Task 4.1b).
 *
 * Menanam BARIS REGISTRY-nya saja, bukan berkas fisiknya: data contoh tidak
 * membawa berkas sungguhan, dan halaman hanya menampilkan tautan tanpa
 * membuka isinya. Berkas fisik lahir dari unggahan petugas lewat
 * `PenyimpananDokumen`.
 *
 * Pivot yang ditanam DIBATASI pada modul yang sudah beralih ke Eloquent.
 * Sisanya menyusul seiring modulnya dikerjakan; menanam pivot bagi induk
 * yang tabelnya masih kosong hanya akan melanggar FK.
 *
 * Idempoten: `updateOrCreate` pada kunci utama, pivot di-`sync` ulang.
 */
class BerkasSeeder extends Seeder
{
    /**
     * Pivot yang induknya sudah bertabel isi. Bertambah seiring Tahap 4-8.
     *
     * @var list<string>
     */
    private const PIVOT_SIAP = [
        'kawasan_transmigrasi_berkas',
        'inventaris_sp_berkas',
        'fasilitas_sp_berkas',
        'infrastruktur_berkas',
    ];

    public function run(): void
    {
        foreach (DummyData::berkas() as $b) {
            Berkas::updateOrCreate(
                ['id_berkas' => $b['id_berkas']],
                // user_id SENGAJA dikosongkan, bukan disalin dari data
                // contoh yang menunjuk akun 1: seeder ini juga dipakai suite
                // Feature yang tak menanam akun sama sekali, dan FK-nya akan
                // gagal. Kolomnya memang nullable -- kanal publik mengunggah
                // tanpa akun (Putaran 12 keputusan 4).
                collect($b)->except(['id_berkas', 'user_id'])->all() + ['user_id' => null],
            );
        }

        foreach (DummyData::berkasPemilik() as $pivot => $baris) {
            if (! in_array($pivot, self::PIVOT_SIAP, true)) {
                continue;
            }

            DB::table($pivot)->delete();
            DB::table($pivot)->insert($baris);
        }
    }
}
