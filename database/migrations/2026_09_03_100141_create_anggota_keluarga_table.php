<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 5, tabel `anggota_keluarga`.
 *
 * Satu baris per anggota keluarga SELAIN kepala keluarga. Mutasi (meninggal/
 * pindah) DITANDAI lewat `status` + `tanggal_peristiwa`, tidak dihapus. `usia`
 * diturunkan dari `tanggal_lahir`. CASCADE mengikuti `transmigran`. Soft delete aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anggota_keluarga', function (Blueprint $table) {
            $table->id('id_anggota_keluarga');
            $table->unsignedBigInteger('transmigran_id');
            $table->enum('hubungan', ['Istri', 'Suami', 'Anak', 'Anak Angkat', 'Orang Tua', 'Famili Lain']);
            $table->string('nama_lengkap', 255);
            $table->char('nik', 16)->nullable();
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan'])->nullable();
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->enum('agama', ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'])->nullable();
            $table->enum('kegiatan', ['Belum Sekolah', 'Masih Sekolah', 'Bekerja', 'Tidak Bekerja'])->nullable();
            $table->enum('pendidikan_terakhir', ['Tidak Sekolah', 'SD', 'SMP', 'SMA/SMK', 'Diploma', 'S1', 'S2', 'S3'])->nullable();
            $table->string('pekerjaan', 100)->nullable();
            $table->decimal('pendapatan_per_bulan', 15, 2)->nullable();
            $table->string('telepon', 20)->nullable();
            $table->string('keterangan', 1000)->nullable();
            $table->enum('status', ['Aktif', 'Meninggal', 'Pindah'])->default('Aktif');
            $table->date('tanggal_peristiwa')->nullable();
            $table->string('keterangan_peristiwa', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('transmigran_id', 'idx_anggota_keluarga_transmigran');
            $table->index('nama_lengkap', 'idx_anggota_keluarga_nama');
            $table->index('nik', 'idx_anggota_keluarga_nik');
            $table->index('status', 'idx_anggota_keluarga_status');

            $table->foreign('transmigran_id', 'fk_anggota_keluarga_transmigran')
                ->references('id_transmigran')->on('transmigran')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anggota_keluarga');
    }
};
