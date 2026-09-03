<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 8, tabel `penanaman`.
 *
 * Berpusat pada poktan (bukan lahan/petani). Sumbu waktu = `periode_tanam`
 * CHAR(7) YYYY-MM. `saprotan_distribusi_id` + `volume_benih` WAJIB (termasuk
 * bibit swadaya). Status panen & luas kelompok = turunan. UNIQUE
 * (poktan, komoditas, periode) DICABUT 2026-09-01. Soft delete aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penanaman', function (Blueprint $table) {
            $table->id('id_penanaman');
            $table->unsignedBigInteger('poktan_id');
            $table->unsignedBigInteger('komoditas_id');
            $table->unsignedBigInteger('saprotan_distribusi_id');
            $table->decimal('volume_benih', 12, 3);
            $table->decimal('realisasi_tanam', 12, 2);
            $table->char('periode_tanam', 7);
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('poktan_id', 'idx_penanaman_poktan');
            $table->index('komoditas_id', 'idx_penanaman_komoditas');
            $table->index('periode_tanam', 'idx_penanaman_periode');
            $table->index('saprotan_distribusi_id', 'idx_penanaman_saprotan_distribusi');

            $table->foreign('poktan_id', 'fk_penanaman_poktan')
                ->references('id_poktan')->on('poktan')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('komoditas_id', 'fk_penanaman_komoditas')
                ->references('id_komoditas')->on('komoditas')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('saprotan_distribusi_id', 'fk_penanaman_saprotan_distribusi')
                ->references('id_saprotan_distribusi')->on('saprotan_distribusi')
                ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penanaman');
    }
};
