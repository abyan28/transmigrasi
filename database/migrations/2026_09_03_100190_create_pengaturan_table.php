<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- tabel `pengaturan` (Task 9.6).
 *
 * Penyimpanan kunci-nilai untuk Pengelolaan Konten Sistem (CMS): identitas
 * aplikasi, kop dokumen laporan, narasi profil, portal warga, pengumuman.
 * `nilai` selalu TEXT; `tipe` menandai cara membacanya kembali (teks, boolean,
 * json, berkas). Berkas (logo/favicon) menyimpan `id_berkas` sebagai `nilai`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan', function (Blueprint $table) {
            $table->string('kunci', 100)->primary();
            $table->text('nilai')->nullable();
            $table->string('tipe', 20)->default('teks');   // teks | boolean | json | berkas
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan');
    }
};
