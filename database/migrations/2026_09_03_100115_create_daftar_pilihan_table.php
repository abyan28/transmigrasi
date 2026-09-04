<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 4, tabel `daftar_pilihan`.
 *
 * Ditempatkan LEBIH AWAL dari domainnya (topological sort): `berkas` menaut
 * `jenis_berkas_id` -> `daftar_pilihan`, dan `satuan_permukiman` menaut `berkas_id`.
 *
 * Nilai dropdown yang dikelola Admin (`data-dictionary.md` 5.6). `jenis`
 * ber-ENUM 14 nilai; `jenis_dokumen_lahan` DICABUT Putaran 15 -- jangan
 * ditambahkan kembali. Self-FK `bidang_id` hanya untuk jenis `kategori_pengaduan`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daftar_pilihan', function (Blueprint $table) {
            $table->id('id_daftar_pilihan');
            $table->enum('jenis', [
                'sumber_dana', 'status_penyerahan', 'kondisi', 'kondisi_rumah',
                'status_hunian', 'tipe_komoditas', 'prioritas_pengaduan',
                'jabatan_anggota_poktan', 'jenis_infrastruktur', 'jenis_fasilitas',
                'bidang_pengaduan', 'kategori_pengaduan', 'jenis_alsintan', 'jenis_inventaris',
            ]);
            $table->string('nilai', 100);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->decimal('nilai_skor', 3, 2)->nullable();
            $table->unsignedBigInteger('bidang_id')->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();

            $table->unique(['jenis', 'nilai'], 'uq_daftar_pilihan_jenis_nilai');
            $table->index('jenis', 'idx_daftar_pilihan_jenis');
            $table->index('is_aktif', 'idx_daftar_pilihan_aktif');
            $table->index('bidang_id', 'idx_daftar_pilihan_bidang');

            $table->foreign('bidang_id', 'fk_daftar_pilihan_bidang')
                ->references('id_daftar_pilihan')->on('daftar_pilihan')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daftar_pilihan');
    }
};
