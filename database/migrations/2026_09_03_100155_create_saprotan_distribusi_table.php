<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 6, tabel `saprotan_distribusi`.
 *
 * Satu baris per poktan penerima. Sisa benih DIHITUNG per baris ini
 * (jumlah - SUM(penanaman.volume_benih)), tidak disimpan. SP ikut poktan.
 * TANPA soft delete. FK saprotan CASCADE, poktan RESTRICT.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saprotan_distribusi', function (Blueprint $table) {
            $table->id('id_saprotan_distribusi');
            $table->unsignedBigInteger('saprotan_id');
            $table->unsignedBigInteger('poktan_id');
            $table->decimal('jumlah', 12, 3);
            $table->date('tanggal_serah')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index('saprotan_id', 'idx_saprotan_distribusi_saprotan');
            $table->index('poktan_id', 'idx_saprotan_distribusi_poktan');

            $table->foreign('saprotan_id', 'fk_saprotan_distribusi_saprotan')
                ->references('id_saprotan')->on('saprotan')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('poktan_id', 'fk_saprotan_distribusi_poktan')
                ->references('id_poktan')->on('poktan')
                ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saprotan_distribusi');
    }
};
