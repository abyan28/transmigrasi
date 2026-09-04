<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rename modul izin `referensi` -> `daftar_pilihan` (2026-09-04).
 *
 * Menyertai penggantian nama tabel `referensi` menjadi `daftar_pilihan`.
 * Sub-menunya sudah bernama "Daftar Pilihan" sejak Tahap 2, sehingga modul
 * izin yang masih bernama `referensi` menyisakan dua istilah untuk satu hal.
 *
 * MEMPERBARUI BARIS YANG ADA, bukan menghapus lalu menanam ulang. Alasannya
 * `role_permission` menunjuk `id_permission`: menghapus barisnya memutus
 * seluruh pemberian izin yang sudah dipasang Admin, dan setiap role diam-diam
 * kehilangan akses menu Daftar Pilihan. Dengan UPDATE, id-nya tetap sehingga
 * pivotnya utuh tanpa perlu disentuh.
 *
 * Idempoten: pada basis data baru tabelnya masih kosong dan UPDATE ini tidak
 * mengenai baris apa pun -- `DaftarPilihanSeeder` yang menanam nama barunya.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('permission')->where('modul', 'referensi')->update([
            'modul' => 'daftar_pilihan',
            'label' => DB::raw("REPLACE(label, 'Data master referensi', 'Data master daftar pilihan')"),
            'nama' => DB::raw("REPLACE(nama, 'referensi.', 'daftar_pilihan.')"),
        ]);
    }

    public function down(): void
    {
        DB::table('permission')->where('modul', 'daftar_pilihan')->update([
            'modul' => 'referensi',
            'label' => DB::raw("REPLACE(label, 'Data master daftar pilihan', 'Data master referensi')"),
            'nama' => DB::raw("REPLACE(nama, 'daftar_pilihan.', 'referensi.')"),
        ]);
    }
};
