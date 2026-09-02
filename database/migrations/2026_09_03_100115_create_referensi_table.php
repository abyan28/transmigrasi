<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 4, tabel `referensi`.
 *
 * Ditempatkan LEBIH AWAL dari domainnya (topological sort): `berkas` menaut
 * `jenis_berkas_id` -> `referensi`, dan `satuan_permukiman` menaut `berkas_id`.
 *
 * Nilai dropdown yang dikelola Admin (`data-dictionary.md` 5.6). `jenis`
 * ber-ENUM 14 nilai; `jenis_dokumen_lahan` DICABUT Putaran 15 -- jangan
 * ditambahkan kembali. Self-FK `bidang_id` hanya untuk jenis `kategori_pengaduan`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referensi', function (Blueprint $table) {
            $table->id('id_referensi');
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

            $table->unique(['jenis', 'nilai'], 'uq_referensi_jenis_nilai');
            $table->index('jenis', 'idx_referensi_jenis');
            $table->index('is_aktif', 'idx_referensi_aktif');
            $table->index('bidang_id', 'idx_referensi_bidang');

            $table->foreign('bidang_id', 'fk_referensi_bidang')
                ->references('id_referensi')->on('referensi')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referensi');
    }
};
