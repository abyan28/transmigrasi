<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 4b, pivot `penanaman_berkas`
 * (berita acara tanam, foto hamparan, bukti benih). Bentuk baku pivot `*_berkas`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penanaman_berkas', function (Blueprint $table) {
            $table->id('id_penanaman_berkas');
            $table->unsignedBigInteger('penanaman_id');
            $table->unsignedBigInteger('berkas_id');
            $table->string('peran', 30);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->unique(['penanaman_id', 'berkas_id'], 'uq_penanaman_berkas');
            $table->index('berkas_id', 'idx_penanaman_berkas_berkas');
            $table->index('peran', 'idx_penanaman_berkas_peran');

            $table->foreign('penanaman_id', 'fk_penanaman_berkas_induk')
                ->references('id_penanaman')->on('penanaman')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('berkas_id', 'fk_penanaman_berkas_berkas')
                ->references('id_berkas')->on('berkas')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penanaman_berkas');
    }
};
