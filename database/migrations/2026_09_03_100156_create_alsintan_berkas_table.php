<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 4b, pivot `alsintan_berkas`
 * (foto barang, berita acara pengadaan). Bentuk baku pivot `*_berkas`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alsintan_berkas', function (Blueprint $table) {
            $table->id('id_alsintan_berkas');
            $table->unsignedBigInteger('alsintan_id');
            $table->unsignedBigInteger('berkas_id');
            $table->string('peran', 30);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->unique(['alsintan_id', 'berkas_id'], 'uq_alsintan_berkas');
            $table->index('berkas_id', 'idx_alsintan_berkas_berkas');
            $table->index('peran', 'idx_alsintan_berkas_peran');

            $table->foreign('alsintan_id', 'fk_alsintan_berkas_induk')
                ->references('id_alsintan')->on('alsintan')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('berkas_id', 'fk_alsintan_berkas_berkas')
                ->references('id_berkas')->on('berkas')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alsintan_berkas');
    }
};
