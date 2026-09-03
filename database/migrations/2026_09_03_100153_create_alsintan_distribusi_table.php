<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 6, tabel `alsintan_distribusi`.
 *
 * Satu baris per poktan penerima. `kondisi` diamati per unit (VARCHAR REF).
 * `penanda_terima_id` -> anggota poktan penanda tangan BA (bukan pemilik).
 * `satuan_permukiman_id` turunan (ikut poktan) -> tidak disimpan. TANPA soft delete.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alsintan_distribusi', function (Blueprint $table) {
            $table->id('id_alsintan_distribusi');
            $table->unsignedBigInteger('alsintan_id');
            $table->unsignedBigInteger('poktan_id');
            $table->unsignedInteger('jumlah');
            $table->string('kondisi', 20)->nullable();
            $table->unsignedBigInteger('penanda_terima_id')->nullable();
            $table->date('tanggal_serah')->nullable();
            $table->unsignedBigInteger('foto_berkas_id')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index('alsintan_id', 'idx_alsintan_distribusi_alsintan');
            $table->index('poktan_id', 'idx_alsintan_distribusi_poktan');
            $table->index('penanda_terima_id', 'idx_alsintan_distribusi_penanda');
            $table->index('foto_berkas_id', 'idx_alsintan_distribusi_foto');

            $table->foreign('alsintan_id', 'fk_alsintan_distribusi_alsintan')
                ->references('id_alsintan')->on('alsintan')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('poktan_id', 'fk_alsintan_distribusi_poktan')
                ->references('id_poktan')->on('poktan')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('penanda_terima_id', 'fk_alsintan_distribusi_penanda')
                ->references('id_anggota_poktan')->on('anggota_poktan')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('foto_berkas_id', 'fk_alsintan_distribusi_foto')
                ->references('id_berkas')->on('berkas')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alsintan_distribusi');
    }
};
