<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 4, tabel `komoditas`.
 *
 * `tipe` = VARCHAR REF(jenis=tipe_komoditas), bukan ENUM: nilainya dikelola
 * tabel `referensi`. `slug` = pengenal publik URL. Soft delete aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('komoditas', function (Blueprint $table) {
            $table->id('id_komoditas');
            $table->unsignedBigInteger('satuan_id');
            $table->string('nama', 100);
            $table->string('slug', 120);
            $table->string('tipe', 20);
            $table->boolean('is_unggulan')->default(false);
            $table->text('deskripsi')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('nama', 'uq_komoditas_nama');
            $table->unique('slug', 'uq_komoditas_slug');
            $table->index('tipe', 'idx_komoditas_tipe');
            $table->index('is_unggulan', 'idx_komoditas_unggulan');
            $table->index('satuan_id', 'idx_komoditas_satuan');

            $table->foreign('satuan_id', 'fk_komoditas_satuan')
                ->references('id_satuan')->on('satuan')
                ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('komoditas');
    }
};
