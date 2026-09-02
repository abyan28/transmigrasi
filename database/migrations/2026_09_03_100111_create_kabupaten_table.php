<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 2, tabel `kabupaten`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kabupaten', function (Blueprint $table) {
            $table->id('id_kabupaten');
            $table->unsignedBigInteger('provinsi_id');
            $table->string('nama', 100);
            $table->string('kode', 10)->nullable();
            $table->timestamps();

            $table->unique(['provinsi_id', 'nama'], 'uq_kabupaten_provinsi_nama');

            $table->foreign('provinsi_id', 'fk_kabupaten_provinsi')
                ->references('id_provinsi')->on('provinsi')
                ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kabupaten');
    }
};
