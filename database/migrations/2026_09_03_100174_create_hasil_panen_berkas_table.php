<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 4b, pivot `hasil_panen_berkas`
 * (berita acara panen, foto hamparan, bukti timbangan). Bentuk baku pivot `*_berkas`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hasil_panen_berkas', function (Blueprint $table) {
            $table->id('id_hasil_panen_berkas');
            $table->unsignedBigInteger('hasil_panen_id');
            $table->unsignedBigInteger('berkas_id');
            $table->string('peran', 30);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->unique(['hasil_panen_id', 'berkas_id'], 'uq_hasil_panen_berkas');
            $table->index('berkas_id', 'idx_hasil_panen_berkas_berkas');
            $table->index('peran', 'idx_hasil_panen_berkas_peran');

            $table->foreign('hasil_panen_id', 'fk_hasil_panen_berkas_induk')
                ->references('id_hasil_panen')->on('hasil_panen')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('berkas_id', 'fk_hasil_panen_berkas_berkas')
                ->references('id_berkas')->on('berkas')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hasil_panen_berkas');
    }
};
