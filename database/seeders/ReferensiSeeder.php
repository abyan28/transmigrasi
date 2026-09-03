<?php

namespace Database\Seeders;

use App\Models\Referensi;
use App\Support\DummyData;
use Illuminate\Database\Seeder;

/**
 * Daftar Pilihan -- nilai dropdown yang dikelola Admin (Task 4.7).
 *
 * Sumber kebenaran = `DummyData::referensi()`, pola sama dengan
 * `PermissionRoleSeeder`. Dibaca dari sana, bukan disalin ulang, sebab
 * **id-nya sudah ditunjuk pihak lain**: `PenilaianKondisiSp::parameter()`
 * merujuk `referensi_id` untuk jenis infrastruktur dan fasilitas. Menyusun
 * ulang daftarnya di sini akan menggeser id itu diam-diam dan membuat
 * penilaian kondisi SP menunjuk jenis yang keliru.
 *
 * `bidang_id` menunjuk baris `referensi` lain (self-FK), sehingga baris
 * `bidang_pengaduan` WAJIB tertanam sebelum `kategori_pengaduan`. Urutan itu
 * sudah dijamin `DummyData::referensi()` dan tidak boleh diacak.
 *
 * Idempoten: `updateOrCreate` pada kunci utama.
 */
class ReferensiSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DummyData::referensi() as $b) {
            Referensi::updateOrCreate(
                ['id_referensi' => $b['id_referensi']],
                [
                    'jenis' => $b['jenis'],
                    'nilai' => $b['nilai'],
                    'urutan' => $b['urutan'],
                    'nilai_skor' => $b['nilai_skor'],
                    'bidang_id' => $b['bidang_id'],
                    'is_aktif' => $b['is_aktif'],
                ],
            );
        }
    }
}
