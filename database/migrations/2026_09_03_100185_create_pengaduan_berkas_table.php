<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 4b, pivot `pengaduan_berkas`
 * (beberapa foto bukti dari pelapor). Bentuk baku pivot `*_berkas`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaduan_berkas', function (Blueprint $table) {
            $table->id('id_pengaduan_berkas');
            $table->unsignedBigInteger('pengaduan_id');
            $table->unsignedBigInteger('berkas_id');
            $table->string('peran', 30);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->unique(['pengaduan_id', 'berkas_id'], 'uq_pengaduan_berkas');
            $table->index('berkas_id', 'idx_pengaduan_berkas_berkas');
            $table->index('peran', 'idx_pengaduan_berkas_peran');

            $table->foreign('pengaduan_id', 'fk_pengaduan_berkas_induk')
                ->references('id_pengaduan')->on('pengaduan')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('berkas_id', 'fk_pengaduan_berkas_berkas')
                ->references('id_berkas')->on('berkas')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaduan_berkas');
    }
};
