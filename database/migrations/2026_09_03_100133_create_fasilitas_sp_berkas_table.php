<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 4b, pivot `fasilitas_sp_berkas`
 * (foto kondisi per unit, berita acara). Bentuk baku pivot `*_berkas`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fasilitas_sp_berkas', function (Blueprint $table) {
            $table->id('id_fasilitas_sp_berkas');
            $table->unsignedBigInteger('fasilitas_sp_id');
            $table->unsignedBigInteger('berkas_id');
            $table->string('peran', 30);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->unique(['fasilitas_sp_id', 'berkas_id'], 'uq_fasilitas_sp_berkas');
            $table->index('berkas_id', 'idx_fasilitas_sp_berkas_berkas');
            $table->index('peran', 'idx_fasilitas_sp_berkas_peran');

            $table->foreign('fasilitas_sp_id', 'fk_fasilitas_sp_berkas_induk')
                ->references('id_fasilitas_sp')->on('fasilitas_sp')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('berkas_id', 'fk_fasilitas_sp_berkas_berkas')
                ->references('id_berkas')->on('berkas')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fasilitas_sp_berkas');
    }
};
