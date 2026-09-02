<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 2, tabel `kawasan_transmigrasi`.
 *
 * Cabang PROGRAM dari hierarki wilayah (memotong batas kecamatan). Dikelola
 * pengguna -> soft delete. Pengenal publik URL: `slug` (bukan data pribadi).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kawasan_transmigrasi', function (Blueprint $table) {
            $table->id('id_kawasan_transmigrasi');
            $table->unsignedBigInteger('kabupaten_id');
            $table->string('nama', 150);
            $table->string('slug', 120);
            $table->string('kode_kawasan', 20)->nullable();
            $table->year('tahun_penetapan')->nullable();
            $table->string('nomor_sk', 100)->nullable();
            $table->decimal('luas_total', 12, 2)->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('slug', 'uq_kawasan_slug');
            $table->unique(['kabupaten_id', 'nama'], 'uq_kawasan_kabupaten_nama');
            $table->index('kabupaten_id', 'idx_kawasan_kabupaten');

            $table->foreign('kabupaten_id', 'fk_kawasan_kabupaten')
                ->references('id_kabupaten')->on('kabupaten')
                ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kawasan_transmigrasi');
    }
};
