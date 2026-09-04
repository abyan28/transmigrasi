<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 3, tabel `inventaris_sp`.
 *
 * Barang bergerak milik SP. `jenis_inventaris`/`sumber_dana`/`status_penyerahan`/
 * `kondisi` = VARCHAR REF (dikelola tabel `daftar_pilihan`), bukan ENUM.
 * `rincian_kondisi` JSON: peta kondisi->jumlah unit. Soft delete aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventaris_sp', function (Blueprint $table) {
            $table->id('id_inventaris_sp');
            $table->unsignedBigInteger('satuan_permukiman_id');
            $table->string('jenis_inventaris', 100);
            $table->string('nama_barang', 255);
            $table->unsignedInteger('jumlah')->default(1);
            $table->string('satuan_barang', 50)->nullable();
            $table->year('tahun_perolehan')->nullable();
            $table->string('sumber_dana', 50)->nullable();
            $table->string('status_penyerahan', 30);
            $table->string('kondisi', 20)->nullable();
            $table->json('rincian_kondisi')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('satuan_permukiman_id', 'idx_inventaris_sp_sp');
            $table->index('jenis_inventaris', 'idx_inventaris_sp_jenis');

            $table->foreign('satuan_permukiman_id', 'fk_inventaris_sp_sp')
                ->references('id_satuan_permukiman')->on('satuan_permukiman')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventaris_sp');
    }
};
