<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 9, tabel `infrastruktur`.
 *
 * Pendataan ASET (pelaporan kerusakan -> fitur Pengaduan). `satuan_permukiman_id`
 * = lokasi/pangkal (wajib); cakupan lintas SP ada di pivot `infrastruktur_sp`.
 * `jenis`/`sumber_dana`/`kondisi` = VARCHAR REF. `kondisi` sumber grafik status.
 * Soft delete aktif. FK SP RESTRICT, poktan SET NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('infrastruktur', function (Blueprint $table) {
            $table->id('id_infrastruktur');
            $table->unsignedBigInteger('satuan_permukiman_id');
            $table->unsignedBigInteger('poktan_id')->nullable();
            $table->string('nama', 255);
            $table->string('jenis', 50);
            $table->year('tahun_perolehan')->nullable();
            $table->string('sumber_dana', 50)->nullable();
            $table->string('kondisi', 20);
            $table->string('kapasitas', 100)->nullable();
            $table->decimal('lintang', 10, 7)->nullable();
            $table->decimal('bujur', 10, 7)->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('satuan_permukiman_id', 'idx_infrastruktur_sp');
            $table->index('jenis', 'idx_infrastruktur_jenis');
            $table->index('kondisi', 'idx_infrastruktur_kondisi');
            $table->index('poktan_id', 'idx_infrastruktur_poktan');

            $table->foreign('satuan_permukiman_id', 'fk_infrastruktur_sp')
                ->references('id_satuan_permukiman')->on('satuan_permukiman')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('poktan_id', 'fk_infrastruktur_poktan')
                ->references('id_poktan')->on('poktan')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('infrastruktur');
    }
};
