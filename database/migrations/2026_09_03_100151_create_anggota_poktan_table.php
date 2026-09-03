<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 6, tabel `anggota_poktan`.
 *
 * `transmigran_id` menunjuk KELUARGA yang diwakili. `asal_wakil` memuat 3 nilai
 * (agar satu tipe dipakai bersama `poktan.asal_ketua`); 'Bukan Transmigran'
 * tidak berlaku di sini (ditegakkan aplikasi). Anggota berhenti ditandai
 * 'Sudah Keluar', tidak dihapus. `jabatan` = VARCHAR REF, TANPA Ketua. Soft delete aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anggota_poktan', function (Blueprint $table) {
            $table->id('id_anggota_poktan');
            $table->unsignedBigInteger('poktan_id');
            $table->unsignedBigInteger('transmigran_id');
            $table->enum('asal_wakil', ['Kepala Keluarga', 'Anggota Keluarga', 'Bukan Transmigran'])->default('Kepala Keluarga');
            $table->unsignedBigInteger('anggota_keluarga_id')->nullable();
            $table->string('telepon_wakil', 20)->nullable();
            $table->string('jabatan', 30);
            $table->date('tanggal_masuk');
            $table->enum('status', ['Aktif', 'Tidak Aktif', 'Sudah Keluar']);
            $table->date('tanggal_keluar')->nullable();
            $table->text('alasan_keluar')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['poktan_id', 'transmigran_id'], 'uq_anggota_poktan_poktan_transmigran');
            $table->index('transmigran_id', 'idx_anggota_poktan_transmigran');
            $table->index('status', 'idx_anggota_poktan_status');
            $table->index('anggota_keluarga_id', 'idx_anggota_poktan_anggota_keluarga');

            $table->foreign('poktan_id', 'fk_anggota_poktan_poktan')
                ->references('id_poktan')->on('poktan')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('transmigran_id', 'fk_anggota_poktan_transmigran')
                ->references('id_transmigran')->on('transmigran')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('anggota_keluarga_id', 'fk_anggota_poktan_anggota_keluarga')
                ->references('id_anggota_keluarga')->on('anggota_keluarga')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anggota_poktan');
    }
};
