<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 9, tabel `penanganan_pengaduan`.
 *
 * Riwayat penanganan; setiap perubahan status = satu baris. `status_sebelum`
 * NULL pada baris pertama. Tabel riwayat: TANPA soft delete. FK pengaduan
 * CASCADE, user (petugas penangan) RESTRICT.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penanganan_pengaduan', function (Blueprint $table) {
            $table->id('id_penanganan_pengaduan');
            $table->unsignedBigInteger('pengaduan_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('status_sebelum', ['Menunggu Diterima', 'Diterima', 'Diproses', 'Selesai'])->nullable();
            $table->enum('status_sesudah', ['Menunggu Diterima', 'Diterima', 'Diproses', 'Selesai']);
            $table->date('tanggal_penanganan');
            $table->text('catatan');
            $table->timestamps();

            $table->index('pengaduan_id', 'idx_penanganan_pengaduan_pengaduan');
            $table->index('tanggal_penanganan', 'idx_penanganan_pengaduan_tanggal');
            $table->index('user_id', 'idx_penanganan_pengaduan_user');

            $table->foreign('pengaduan_id', 'fk_penanganan_pengaduan_pengaduan')
                ->references('id_pengaduan')->on('pengaduan')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('user_id', 'fk_penanganan_pengaduan_user')
                ->references('id_user')->on('user')
                ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penanganan_pengaduan');
    }
};
