<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 4b, pivot
 * `penanganan_pengaduan_berkas` (dokumen tindak lanjut tiap tahap).
 * Bentuk baku pivot `*_berkas`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penanganan_pengaduan_berkas', function (Blueprint $table) {
            $table->id('id_penanganan_pengaduan_berkas');
            $table->unsignedBigInteger('penanganan_pengaduan_id');
            $table->unsignedBigInteger('berkas_id');
            $table->string('peran', 30);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->unique(['penanganan_pengaduan_id', 'berkas_id'], 'uq_penanganan_pengaduan_berkas');
            $table->index('berkas_id', 'idx_penanganan_pengaduan_berkas_berkas');
            $table->index('peran', 'idx_penanganan_pengaduan_berkas_peran');

            $table->foreign('penanganan_pengaduan_id', 'fk_penanganan_pengaduan_berkas_induk')
                ->references('id_penanganan_pengaduan')->on('penanganan_pengaduan')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('berkas_id', 'fk_penanganan_pengaduan_berkas_berkas')
                ->references('id_berkas')->on('berkas')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penanganan_pengaduan_berkas');
    }
};
