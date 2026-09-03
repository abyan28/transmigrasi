<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 8, tabel `hasil_panen`.
 *
 * Satu penanaman -> paling banyak satu baris panen (ditegakkan aplikasi).
 * `satuan_id` DISALIN dari komoditas saat simpan (snapshot). `poktan_id`
 * DICABUT (turunan dari penanaman). `produksi` disimpan apa adanya, tanpa
 * konversi. `uuid` = pengenal publik. Soft delete aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hasil_panen', function (Blueprint $table) {
            $table->id('id_hasil_panen');
            $table->char('uuid', 36);
            $table->unsignedBigInteger('penanaman_id');
            $table->unsignedBigInteger('satuan_id');
            $table->char('periode_panen', 7);
            $table->decimal('realisasi_panen', 12, 2);
            $table->decimal('puso', 12, 2)->nullable();
            $table->decimal('produktivitas', 12, 3);
            $table->decimal('produksi', 12, 3);
            $table->decimal('harga_jual', 15, 2)->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('uuid', 'uq_hasil_panen_uuid');
            $table->index('penanaman_id', 'idx_hasil_panen_penanaman');
            $table->index('periode_panen', 'idx_hasil_panen_periode');
            $table->index('satuan_id', 'idx_hasil_panen_satuan');

            $table->foreign('penanaman_id', 'fk_hasil_panen_penanaman')
                ->references('id_penanaman')->on('penanaman')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('satuan_id', 'fk_hasil_panen_satuan')
                ->references('id_satuan')->on('satuan')
                ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hasil_panen');
    }
};
