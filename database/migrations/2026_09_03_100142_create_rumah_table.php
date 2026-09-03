<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 5, tabel `rumah`.
 *
 * Relasi rumah<->KK satu-ke-satu dua arah: FK di `rumah`, `transmigran_id`
 * UNIQUE nullable (NULL = rumah kosong). UNIQUE wajib di level DB (`rules.md`
 * 6a.6). `kondisi`/`status_hunian` = VARCHAR REF, bukan ENUM. FK SP RESTRICT,
 * FK transmigran SET NULL. `uuid` = pengenal publik. Soft delete aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rumah', function (Blueprint $table) {
            $table->id('id_rumah');
            $table->char('uuid', 36);
            $table->unsignedBigInteger('satuan_permukiman_id');
            $table->unsignedBigInteger('transmigran_id')->nullable();
            $table->string('no_rumah', 50)->nullable();
            $table->string('kondisi', 20);
            $table->string('status_hunian', 20);
            $table->text('alasan_tidak_dihuni')->nullable();
            $table->year('tahun_pembangunan')->nullable();
            $table->decimal('luas_bangunan', 8, 2)->nullable();
            $table->decimal('lintang', 10, 7)->nullable();
            $table->decimal('bujur', 10, 7)->nullable();
            $table->text('catatan_hunian')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('uuid', 'uq_rumah_uuid');
            $table->unique('transmigran_id', 'uq_rumah_transmigran');
            $table->index('satuan_permukiman_id', 'idx_rumah_sp');
            $table->index('status_hunian', 'idx_rumah_status_hunian');
            $table->index('kondisi', 'idx_rumah_kondisi');

            $table->foreign('satuan_permukiman_id', 'fk_rumah_sp')
                ->references('id_satuan_permukiman')->on('satuan_permukiman')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('transmigran_id', 'fk_rumah_transmigran')
                ->references('id_transmigran')->on('transmigran')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rumah');
    }
};
