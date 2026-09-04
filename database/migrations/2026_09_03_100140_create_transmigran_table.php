<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 5, tabel `transmigran`.
 *
 * Satu baris = satu kepala keluarga / KK. `uuid` = pengenal publik URL.
 * `usia`/`jumlah_anggota_keluarga` TIDAK disimpan (diturunkan). `status_sertifikat`
 * (SHM) melekat di KK, bukan per bidang lahan (`rules.md` 7.6). `pekerjaan_kepala_keluarga`
 * sengaja teks bebas. FK SP + daerah asal RESTRICT. Soft delete aktif.
 *
 * `tahun_keluar` (2026-09-04): tahun `status_tinggal` berubah ke Pindah
 * Penduduk/Tidak Aktif -- sumber dashboard "KK Keluar per tahun".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transmigran', function (Blueprint $table) {
            $table->id('id_transmigran');
            $table->char('uuid', 36);
            $table->unsignedBigInteger('satuan_permukiman_id');
            $table->char('nik', 16);
            $table->char('no_kk', 16);
            $table->string('nama_kepala_keluarga', 255);
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan'])->nullable();
            $table->enum('agama', ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'])->nullable();
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->enum('pendidikan_terakhir', ['Tidak Sekolah', 'SD', 'SMP', 'SMA/SMK', 'Diploma', 'S1', 'S2', 'S3'])->nullable();
            $table->string('pekerjaan_kepala_keluarga', 100);
            $table->decimal('pendapatan_per_bulan', 15, 2)->nullable();
            $table->unsignedBigInteger('daerah_asal_kabupaten_id')->nullable();
            $table->year('tahun_kedatangan');
            $table->enum('status_tinggal', ['Aktif', 'Pindah Penduduk', 'Tidak Aktif']);
            $table->year('tahun_keluar')->nullable();
            $table->enum('status_anggota_poktan', ['Ya', 'Tidak']);
            $table->enum('status_sertifikat', ['Sudah', 'Belum', 'Belum Didata'])->default('Belum Didata');
            $table->string('telepon', 20)->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('uuid', 'uq_transmigran_uuid');
            $table->unique('nik', 'uq_transmigran_nik');
            $table->unique('no_kk', 'uq_transmigran_no_kk');
            $table->index('satuan_permukiman_id', 'idx_transmigran_sp');
            $table->index('nama_kepala_keluarga', 'idx_transmigran_nama');
            $table->index('tahun_kedatangan', 'idx_transmigran_tahun_kedatangan');
            $table->index('tahun_keluar', 'idx_transmigran_tahun_keluar');
            $table->index('pekerjaan_kepala_keluarga', 'idx_transmigran_pekerjaan');
            $table->index('status_sertifikat', 'idx_transmigran_sertifikat');
            $table->index('daerah_asal_kabupaten_id', 'idx_transmigran_daerah_asal');
            // Task 9.4 (2026-09-05): status_tinggal difilter/di-GROUP BY hampir
            // tiap kueri RekapDashboard; pendidikan_terakhir di-GROUP BY
            // pendidikanPerTahun(). Lihat catatan schema.sql.
            $table->index('status_tinggal', 'idx_transmigran_status_tinggal');
            $table->index('pendidikan_terakhir', 'idx_transmigran_pendidikan');

            $table->foreign('satuan_permukiman_id', 'fk_transmigran_sp')
                ->references('id_satuan_permukiman')->on('satuan_permukiman')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('daerah_asal_kabupaten_id', 'fk_transmigran_daerah_asal')
                ->references('id_kabupaten')->on('kabupaten')
                ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transmigran');
    }
};
