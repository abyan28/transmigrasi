<?php

namespace Database\Seeders;

use App\Models\RiwayatPenghunian;
use App\Models\Rumah;
use App\Support\DummyData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Rumah + riwayat penghunian (Task 5.3 / 5.4).
 *
 * `id_rumah` dipaksa sama seperti data contoh supaya tautan rincian, filter,
 * dan data lahan/poktan yang masih `DummyData` tetap menunjuk baris yang sama.
 * `uuid` ditulis sekali lalu dipertahankan (pengenal publik URL).
 *
 * `transmigran_id` UNIQUE nullable (NULL = rumah kosong). Dijalankan SETELAH
 * `TransmigranSeeder` (FK) dan SEBELUM `BerkasSeeder` (pivot `rumah_berkas`).
 */
class RumahSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DummyData::rumah() as $r) {
            $baris = Rumah::withTrashed()->firstOrNew(['id_rumah' => $r['id_rumah']]);
            $baris->uuid ??= (string) Str::uuid();
            $baris->fill([
                'satuan_permukiman_id' => $r['satuan_permukiman_id'],
                'transmigran_id' => $r['transmigran_id'] ?? null,
                'no_rumah' => $r['no_rumah'] ?? null,
                'kondisi' => $r['kondisi'],
                'status_hunian' => $r['status_hunian'],
                'alasan_tidak_dihuni' => $r['alasan_tidak_dihuni'] ?? null,
                'tahun_pembangunan' => $r['tahun_pembangunan'] ?? null,
                'luas_bangunan' => $r['luas_bangunan'] ?? null,
                'lintang' => $r['lintang'] ?? null,
                'bujur' => $r['bujur'] ?? null,
                'catatan_hunian' => $r['catatan_hunian'] ?? null,
            ]);
            $baris->save();
        }

        foreach (DummyData::riwayatPenghunian() as $p) {
            RiwayatPenghunian::updateOrCreate(
                ['id_riwayat_penghunian' => $p['id_riwayat_penghunian']],
                [
                    'rumah_id' => $p['rumah_id'],
                    'transmigran_id' => $p['transmigran_id'],
                    'tanggal_masuk' => $p['tanggal_masuk'],
                    'tanggal_keluar' => $p['tanggal_keluar'] ?? null,
                    'alasan_keluar' => $p['alasan_keluar'] ?? null,
                    'keterangan' => $p['keterangan'] ?? null,
                ],
            );
        }
    }
}
