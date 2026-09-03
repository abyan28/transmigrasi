<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 4b, pivot `rumah_berkas`
 * (foto beberapa sisi, dokumen pendukung). Bentuk baku pivot `*_berkas`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rumah_berkas', function (Blueprint $table) {
            $table->id('id_rumah_berkas');
            $table->unsignedBigInteger('rumah_id');
            $table->unsignedBigInteger('berkas_id');
            $table->string('peran', 30);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->unique(['rumah_id', 'berkas_id'], 'uq_rumah_berkas');
            $table->index('berkas_id', 'idx_rumah_berkas_berkas');
            $table->index('peran', 'idx_rumah_berkas_peran');

            $table->foreign('rumah_id', 'fk_rumah_berkas_induk')
                ->references('id_rumah')->on('rumah')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('berkas_id', 'fk_rumah_berkas_berkas')
                ->references('id_berkas')->on('berkas')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rumah_berkas');
    }
};
