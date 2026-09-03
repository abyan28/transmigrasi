<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 5, tabel `riwayat_kepala_keluarga`.
 *
 * Jejak suksesi kepala keluarga; append-only; kedua sisi identitas
 * didenormalisasi (tanpa FK ke `anggota_keluarga` yang akan menggantung).
 * TANPA soft delete. FK transmigran RESTRICT.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_kepala_keluarga', function (Blueprint $table) {
            $table->id('id_riwayat_kepala_keluarga');
            $table->unsignedBigInteger('transmigran_id');
            $table->char('nik_lama', 16);
            $table->string('nama_lama', 255);
            $table->char('nik_baru', 16);
            $table->string('nama_baru', 255);
            $table->char('no_kk_lama', 16);
            $table->char('no_kk_baru', 16);
            $table->date('tanggal_pergantian');
            $table->enum('alasan', ['Meninggal', 'Pindah atau Merantau', 'Cerai', 'Lainnya']);
            $table->enum('hubungan_pengganti', ['Istri', 'Suami', 'Anak', 'Anak Angkat', 'Orang Tua', 'Famili Lain']);
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index('transmigran_id', 'idx_riwayat_kk_transmigran');
            $table->index('tanggal_pergantian', 'idx_riwayat_kk_tanggal');
            $table->index('alasan', 'idx_riwayat_kk_alasan');

            $table->foreign('transmigran_id', 'fk_riwayat_kk_transmigran')
                ->references('id_transmigran')->on('transmigran')
                ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_kepala_keluarga');
    }
};
