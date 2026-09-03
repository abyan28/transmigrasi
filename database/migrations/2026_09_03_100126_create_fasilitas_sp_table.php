<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 3, tabel `fasilitas_sp`.
 *
 * Bangunan/fasilitas tetap milik SP. `jenis_fasilitas` tetap ENUM (bukan teks
 * bebas) sebab dipakai penilaian kondisi SP. `satuan_permukiman_id` = lokasi/
 * pangkal; SP yang dilayani ada di pivot `fasilitas_sp_cakupan`. Soft delete aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fasilitas_sp', function (Blueprint $table) {
            $table->id('id_fasilitas_sp');
            $table->unsignedBigInteger('satuan_permukiman_id');
            $table->enum('jenis_fasilitas', [
                'Kesehatan', 'Pendidikan Dasar', 'Pendidikan Lanjutan', 'Ibadah',
                'Balai Pertemuan', 'Pasar atau Kios', 'Olahraga', 'Keamanan', 'Lainnya',
            ]);
            $table->string('nama_fasilitas', 255);
            $table->unsignedInteger('jumlah')->default(1);
            $table->year('tahun_perolehan')->nullable();
            $table->string('sumber_dana', 50)->nullable();
            $table->string('status_penyerahan', 30);
            $table->string('kondisi', 20)->nullable();
            $table->json('rincian_kondisi')->nullable();
            $table->decimal('lintang', 10, 7)->nullable();
            $table->decimal('bujur', 10, 7)->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('satuan_permukiman_id', 'idx_fasilitas_sp_sp');
            $table->index('jenis_fasilitas', 'idx_fasilitas_sp_jenis');

            $table->foreign('satuan_permukiman_id', 'fk_fasilitas_sp_sp')
                ->references('id_satuan_permukiman')->on('satuan_permukiman')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fasilitas_sp');
    }
};
