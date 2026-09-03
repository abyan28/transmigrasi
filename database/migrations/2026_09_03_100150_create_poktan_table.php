<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 6, tabel `poktan`.
 *
 * Ketua punya 3 asal-usul (`asal_ketua`). `jumlah_anggota` & `luas_lahan_kelompok`
 * TIDAK disimpan (diturunkan). `luas_*_ketua`/`nama_ketua`/`nik_ketua` hanya
 * bila `asal_ketua = Bukan Transmigran`. `slug` = pengenal publik. Soft delete aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poktan', function (Blueprint $table) {
            $table->id('id_poktan');
            $table->unsignedBigInteger('satuan_permukiman_id');
            $table->string('slug', 120);
            $table->string('nama', 255);
            $table->enum('asal_ketua', ['Kepala Keluarga', 'Anggota Keluarga', 'Bukan Transmigran'])->default('Kepala Keluarga');
            $table->unsignedBigInteger('ketua_transmigran_id')->nullable();
            $table->unsignedBigInteger('ketua_anggota_keluarga_id')->nullable();
            $table->string('nama_ketua', 255)->nullable();
            $table->char('nik_ketua', 16)->nullable();
            $table->year('tahun_berdiri')->nullable();
            $table->string('telepon_ketua', 20)->nullable();
            $table->string('email_ketua', 255)->nullable();
            $table->string('alamat_ketua', 255)->nullable();
            $table->decimal('luas_kering_ketua', 12, 2)->nullable();
            $table->decimal('luas_basah_ketua', 12, 2)->nullable();
            $table->decimal('lintang', 10, 7)->nullable();
            $table->decimal('bujur', 10, 7)->nullable();
            $table->unsignedBigInteger('berkas_id')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('slug', 'uq_poktan_slug');
            $table->unique('nama', 'uq_poktan_nama');
            $table->index('satuan_permukiman_id', 'idx_poktan_sp');
            $table->index('ketua_transmigran_id', 'idx_poktan_ketua_transmigran');
            $table->index('ketua_anggota_keluarga_id', 'idx_poktan_ketua_anggota_keluarga');
            $table->index('berkas_id', 'idx_poktan_berkas');

            $table->foreign('satuan_permukiman_id', 'fk_poktan_sp')
                ->references('id_satuan_permukiman')->on('satuan_permukiman')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('ketua_transmigran_id', 'fk_poktan_ketua_transmigran')
                ->references('id_transmigran')->on('transmigran')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('ketua_anggota_keluarga_id', 'fk_poktan_ketua_anggota_keluarga')
                ->references('id_anggota_keluarga')->on('anggota_keluarga')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('berkas_id', 'fk_poktan_berkas')
                ->references('id_berkas')->on('berkas')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poktan');
    }
};
