<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 2, tabel `desa`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('desa', function (Blueprint $table) {
            $table->id('id_desa');
            $table->unsignedBigInteger('kecamatan_id');
            $table->string('nama', 100);
            $table->string('kode', 10)->nullable();
            $table->timestamps();

            $table->unique(['kecamatan_id', 'nama'], 'uq_desa_kecamatan_nama');

            $table->foreign('kecamatan_id', 'fk_desa_kecamatan')
                ->references('id_kecamatan')->on('kecamatan')
                ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desa');
    }
};
