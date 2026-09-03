<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 4b, pivot `infrastruktur_berkas`
 * (foto beberapa titik kerusakan). Bentuk baku pivot `*_berkas`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('infrastruktur_berkas', function (Blueprint $table) {
            $table->id('id_infrastruktur_berkas');
            $table->unsignedBigInteger('infrastruktur_id');
            $table->unsignedBigInteger('berkas_id');
            $table->string('peran', 30);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->unique(['infrastruktur_id', 'berkas_id'], 'uq_infrastruktur_berkas');
            $table->index('berkas_id', 'idx_infrastruktur_berkas_berkas');
            $table->index('peran', 'idx_infrastruktur_berkas_peran');

            $table->foreign('infrastruktur_id', 'fk_infrastruktur_berkas_induk')
                ->references('id_infrastruktur')->on('infrastruktur')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('berkas_id', 'fk_infrastruktur_berkas_berkas')
                ->references('id_berkas')->on('berkas')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('infrastruktur_berkas');
    }
};
