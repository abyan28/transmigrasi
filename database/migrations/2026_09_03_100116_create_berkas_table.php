<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 4b, registry `berkas`.
 *
 * Ditempatkan lebih awal dari domainnya (topological sort): `satuan_permukiman`
 * menaut `berkas_id` -> `berkas`. Pivot `*_berkas` menyusul setelah tabel induk
 * domainnya masing-masing (batch berikutnya).
 *
 * Satu tempat METADATA seluruh berkas sistem (Putaran 12). `uuid` = pengenal
 * publik; PK integer tetap kunci internal. `user_id` NULL = unggahan kanal
 * publik tanpa akun. Soft delete: berkas fisik dibersihkan terjadwal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('berkas', function (Blueprint $table) {
            $table->id('id_berkas');
            $table->char('uuid', 36);
            $table->unsignedBigInteger('jenis_berkas_id')->nullable();
            $table->string('nama_file', 255);
            $table->string('nama_asli', 255)->nullable();
            $table->string('path', 255);
            $table->string('mime', 127);
            $table->string('ekstensi', 10);
            $table->unsignedBigInteger('ukuran');
            $table->string('disk', 20)->default('local');
            $table->string('keterangan', 500)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('uuid', 'uq_berkas_uuid');
            $table->index('jenis_berkas_id', 'idx_berkas_jenis');
            $table->index('user_id', 'idx_berkas_user');
            $table->index('disk', 'idx_berkas_disk');

            $table->foreign('jenis_berkas_id', 'fk_berkas_jenis')
                ->references('id_daftar_pilihan')->on('daftar_pilihan')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('user_id', 'fk_berkas_user')
                ->references('id_user')->on('user')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('berkas');
    }
};
