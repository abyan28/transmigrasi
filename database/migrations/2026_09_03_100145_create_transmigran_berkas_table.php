<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 4b, pivot `transmigran_berkas`
 * (KTP, KK, SK penempatan, SHM). Bentuk baku pivot `*_berkas`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transmigran_berkas', function (Blueprint $table) {
            $table->id('id_transmigran_berkas');
            $table->unsignedBigInteger('transmigran_id');
            $table->unsignedBigInteger('berkas_id');
            $table->string('peran', 30);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->unique(['transmigran_id', 'berkas_id'], 'uq_transmigran_berkas');
            $table->index('berkas_id', 'idx_transmigran_berkas_berkas');
            $table->index('peran', 'idx_transmigran_berkas_peran');

            $table->foreign('transmigran_id', 'fk_transmigran_berkas_induk')
                ->references('id_transmigran')->on('transmigran')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('berkas_id', 'fk_transmigran_berkas_berkas')
                ->references('id_berkas')->on('berkas')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transmigran_berkas');
    }
};
