<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 7, tabel `lahan`.
 *
 * SATU BARIS = SATU KELUARGA (Putaran 15). Tepat satu pekarangan + satu lahan
 * usaha per KK (`rules.md` 7.8), masing-masing ber-koordinat sendiri. Kolom
 * pekarangan/usaha NULLABLE: NULL = BELUM MENERIMA, bukan nol hektare.
 * `luas_kering` + `luas_basah` = `luas_usaha` (`rules.md` 7.5). `uuid` =
 * pengenal publik. UNIQUE `transmigran_id`. Soft delete aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lahan', function (Blueprint $table) {
            $table->id('id_lahan');
            $table->char('uuid', 36);
            $table->unsignedBigInteger('transmigran_id');
            $table->unsignedBigInteger('satuan_permukiman_id');
            $table->unsignedBigInteger('poktan_id')->nullable();
            $table->string('kode_lahan', 50)->nullable();
            $table->decimal('luas_pekarangan', 12, 2)->nullable();
            $table->decimal('lintang_pekarangan', 10, 7)->nullable();
            $table->decimal('bujur_pekarangan', 10, 7)->nullable();
            $table->decimal('luas_usaha', 12, 2)->nullable();
            $table->decimal('luas_kering', 12, 2)->nullable();
            $table->decimal('luas_basah', 12, 2)->nullable();
            $table->decimal('lintang_usaha', 10, 7)->nullable();
            $table->decimal('bujur_usaha', 10, 7)->nullable();
            $table->text('tujuan_pemanfaatan')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('uuid', 'uq_lahan_uuid');
            $table->unique('kode_lahan', 'uq_lahan_kode');
            $table->unique('transmigran_id', 'uq_lahan_transmigran');
            $table->index('satuan_permukiman_id', 'idx_lahan_sp');
            $table->index('poktan_id', 'idx_lahan_poktan');

            $table->foreign('transmigran_id', 'fk_lahan_transmigran')
                ->references('id_transmigran')->on('transmigran')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('satuan_permukiman_id', 'fk_lahan_sp')
                ->references('id_satuan_permukiman')->on('satuan_permukiman')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('poktan_id', 'fk_lahan_poktan')
                ->references('id_poktan')->on('poktan')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lahan');
    }
};
