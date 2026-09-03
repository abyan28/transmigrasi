<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 4b, pivot
 * `kawasan_transmigrasi_berkas` (HPL, SK penetapan, peta kawasan).
 *
 * Bentuk baku pivot `*_berkas`: `peran` menggantikan kolom `foto`/`dokumen`
 * lama, `urutan` menentukan tampil pertama. CASCADE ke induk dan ke `berkas`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kawasan_transmigrasi_berkas', function (Blueprint $table) {
            $table->id('id_kawasan_transmigrasi_berkas');
            $table->unsignedBigInteger('kawasan_transmigrasi_id');
            $table->unsignedBigInteger('berkas_id');
            $table->string('peran', 30);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->unique(['kawasan_transmigrasi_id', 'berkas_id'], 'uq_kawasan_transmigrasi_berkas');
            $table->index('berkas_id', 'idx_kawasan_transmigrasi_berkas_berkas');
            $table->index('peran', 'idx_kawasan_transmigrasi_berkas_peran');

            $table->foreign('kawasan_transmigrasi_id', 'fk_kawasan_transmigrasi_berkas_induk')
                ->references('id_kawasan_transmigrasi')->on('kawasan_transmigrasi')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('berkas_id', 'fk_kawasan_transmigrasi_berkas_berkas')
                ->references('id_berkas')->on('berkas')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kawasan_transmigrasi_berkas');
    }
};
