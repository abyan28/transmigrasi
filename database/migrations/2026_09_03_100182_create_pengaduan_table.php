<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 9, tabel `pengaduan`.
 *
 * Kanal PUBLIK tanpa login: `user_id` nullable (kosong bila dari warga).
 * `bidang` nullable (belum ditetapkan). `status` = terkini saja; riwayat di
 * `penanganan_pengaduan`. `kategori`/`bidang`/`prioritas` = VARCHAR REF.
 * `uuid` = pengenal publik. Soft delete aktif. FK user + SP RESTRICT.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaduan', function (Blueprint $table) {
            $table->id('id_pengaduan');
            $table->char('uuid', 36);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('nama_pelapor', 255);
            $table->string('kontak_pelapor', 20);
            $table->string('email_pelapor', 100)->nullable();
            $table->enum('sumber_laporan', ['Publik', 'Petugas']);
            $table->string('ip_pelapor', 45)->nullable();
            $table->unsignedBigInteger('satuan_permukiman_id');
            $table->string('nomor_pengaduan', 30);
            $table->date('tanggal_pengaduan');
            $table->string('kategori', 50);
            $table->string('bidang', 30)->nullable();
            $table->string('judul', 255);
            $table->text('deskripsi');
            $table->enum('status', ['Menunggu Diterima', 'Diterima', 'Diproses', 'Selesai']);
            $table->string('prioritas', 20);
            $table->decimal('lintang', 10, 7)->nullable();
            $table->decimal('bujur', 10, 7)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('uuid', 'uq_pengaduan_uuid');
            $table->unique('nomor_pengaduan', 'uq_pengaduan_nomor');
            $table->index('user_id', 'idx_pengaduan_user');
            $table->index('satuan_permukiman_id', 'idx_pengaduan_sp');
            $table->index('kategori', 'idx_pengaduan_kategori');
            $table->index('bidang', 'idx_pengaduan_bidang');
            $table->index('status', 'idx_pengaduan_status');
            $table->index('prioritas', 'idx_pengaduan_prioritas');
            $table->index('tanggal_pengaduan', 'idx_pengaduan_tanggal');
            $table->index('sumber_laporan', 'idx_pengaduan_sumber');
            $table->index('ip_pelapor', 'idx_pengaduan_ip');

            $table->foreign('user_id', 'fk_pengaduan_user')
                ->references('id_user')->on('user')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('satuan_permukiman_id', 'fk_pengaduan_sp')
                ->references('id_satuan_permukiman')->on('satuan_permukiman')
                ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaduan');
    }
};
