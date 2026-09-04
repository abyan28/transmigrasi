<?php

namespace Database\Seeders;

use App\Models\DaftarPilihan;
use App\Support\DummyData;
use Illuminate\Database\Seeder;

/**
 * Daftar Pilihan -- nilai dropdown yang dikelola Admin (Task 4.7).
 *
 * Sumber kebenaran = `DummyData::daftarPilihan()`, pola sama dengan
 * `PermissionRoleSeeder`. Dibaca dari sana, bukan disalin ulang, sebab
 * **id-nya sudah ditunjuk pihak lain**: `PenilaianKondisiSp::parameter()`
 * merujuk `daftar_pilihan_id` untuk jenis infrastruktur dan fasilitas. Menyusun
 * ulang daftarnya di sini akan menggeser id itu diam-diam dan membuat
 * penilaian kondisi SP menunjuk jenis yang keliru.
 *
 * `bidang_id` menunjuk baris `daftar_pilihan` lain (self-FK), sehingga baris
 * `bidang_pengaduan` WAJIB tertanam sebelum `kategori_pengaduan`. Urutan itu
 * sudah dijamin `DummyData::daftarPilihan()` dan tidak boleh diacak.
 *
 * Idempoten: `updateOrCreate` pada kunci utama.
 */
class DaftarPilihanSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DummyData::daftarPilihan() as $b) {
            DaftarPilihan::updateOrCreate(
                ['id_daftar_pilihan' => $b['id_daftar_pilihan']],
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
