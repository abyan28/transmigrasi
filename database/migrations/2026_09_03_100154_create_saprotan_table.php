<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 6, tabel `saprotan`
 * (induk / pengadaan). `komoditas_id` & `varietas` wajib hanya bila
 * `jenis = Benih` (ditegakkan aplikasi). `tahun_pengadaan` = tahun anggaran.
 * `jadwal_tanam` = rencana YYYY-MM. Penerima selalu poktan. Soft delete aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saprotan', function (Blueprint $table) {
            $table->id('id_saprotan');
            $table->unsignedBigInteger('satuan_id');
            $table->unsignedBigInteger('komoditas_id')->nullable();
            $table->enum('jenis', ['Benih', 'Pupuk', 'Pestisida', 'Mulsa', 'Lainnya']);
            $table->string('nama', 255);
            $table->decimal('jumlah_total', 12, 3);
            $table->string('varietas', 120)->nullable();
            $table->char('jadwal_tanam', 7)->nullable();
            $table->year('tahun_pengadaan');
            $table->string('sumber_dana', 50)->nullable();
            $table->unsignedBigInteger('foto_berkas_id')->nullable();
            $table->unsignedBigInteger('berkas_id')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('satuan_id', 'idx_saprotan_satuan');
            $table->index('komoditas_id', 'idx_saprotan_komoditas');
            $table->index('jenis', 'idx_saprotan_jenis');
            $table->index('tahun_pengadaan', 'idx_saprotan_tahun');
            $table->index('foto_berkas_id', 'idx_saprotan_foto');
            $table->index('berkas_id', 'idx_saprotan_berkas');

            $table->foreign('satuan_id', 'fk_saprotan_satuan')
                ->references('id_satuan')->on('satuan')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('komoditas_id', 'fk_saprotan_komoditas')
                ->references('id_komoditas')->on('komoditas')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('foto_berkas_id', 'fk_saprotan_foto')
                ->references('id_berkas')->on('berkas')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('berkas_id', 'fk_saprotan_berkas')
                ->references('id_berkas')->on('berkas')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saprotan');
    }
};
