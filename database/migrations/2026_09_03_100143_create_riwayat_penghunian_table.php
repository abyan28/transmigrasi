<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 5, tabel `riwayat_penghunian`.
 *
 * Jejak pergantian penghuni; append-only. Tabel riwayat: TANPA soft delete.
 * FK rumah CASCADE, FK transmigran RESTRICT (jejak tak boleh yatim).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_penghunian', function (Blueprint $table) {
            $table->id('id_riwayat_penghunian');
            $table->unsignedBigInteger('rumah_id');
            $table->unsignedBigInteger('transmigran_id');
            $table->date('tanggal_masuk');
            $table->date('tanggal_keluar')->nullable();
            $table->text('alasan_keluar')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index('rumah_id', 'idx_riwayat_penghunian_rumah');
            $table->index('transmigran_id', 'idx_riwayat_penghunian_transmigran');
            $table->index('tanggal_masuk', 'idx_riwayat_penghunian_masuk');
            $table->index('tanggal_keluar', 'idx_riwayat_penghunian_keluar');

            $table->foreign('rumah_id', 'fk_riwayat_penghunian_rumah')
                ->references('id_rumah')->on('rumah')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('transmigran_id', 'fk_riwayat_penghunian_transmigran')
                ->references('id_transmigran')->on('transmigran')
                ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_penghunian');
    }
};
