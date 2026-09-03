<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 6, tabel `alsintan`
 * (induk / pengadaan). Baris ini mendeskripsikan BENDAnya; kepemilikan/kondisi
 * ada di `alsintan_distribusi`. `jenis_alsintan`/`sumber_dana` = VARCHAR REF.
 * Tanpa FK. Soft delete aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alsintan', function (Blueprint $table) {
            $table->id('id_alsintan');
            $table->string('jenis_alsintan', 120);
            $table->string('nama_alat', 255);
            $table->unsignedInteger('jumlah_total');
            $table->year('tahun_pengadaan')->nullable();
            $table->string('sumber_dana', 50)->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('jenis_alsintan', 'idx_alsintan_jenis');
            $table->index('tahun_pengadaan', 'idx_alsintan_tahun');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alsintan');
    }
};
