<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 2, tabel `kecamatan`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kecamatan', function (Blueprint $table) {
            $table->id('id_kecamatan');
            $table->unsignedBigInteger('kabupaten_id');
            $table->string('nama', 100);
            $table->string('kode', 10)->nullable();
            $table->timestamps();

            $table->unique(['kabupaten_id', 'nama'], 'uq_kecamatan_kabupaten_nama');

            $table->foreign('kabupaten_id', 'fk_kecamatan_kabupaten')
                ->references('id_kabupaten')->on('kabupaten')
                ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kecamatan');
    }
};
