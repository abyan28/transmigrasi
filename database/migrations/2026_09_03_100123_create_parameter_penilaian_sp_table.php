<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 4, tabel `parameter_penilaian_sp`.
 *
 * Parameter penilaian kondisi SP + bobotnya (data, bukan konstanta kode).
 * `daftar_pilihan_id` menunjuk baris `daftar_pilihan` (jenis jenis_infrastruktur /
 * jenis_fasilitas) lewat id -- satu-satunya pengecualian "yang tersimpan teks".
 * Tabel rujukan: tanpa soft delete.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parameter_penilaian_sp', function (Blueprint $table) {
            $table->id('id_parameter_penilaian_sp');
            $table->string('kode', 50);
            $table->string('nama', 100);
            $table->enum('tingkat', ['Primer', 'Sekunder', 'Tersier']);
            $table->unsignedTinyInteger('bobot');
            $table->enum('sumber', ['Infrastruktur', 'Fasilitas']);
            $table->unsignedBigInteger('daftar_pilihan_id');
            $table->boolean('is_dinilai')->default(false);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->unique('kode', 'uq_parameter_penilaian_kode');
            $table->index('tingkat', 'idx_parameter_penilaian_tingkat');
            $table->index('daftar_pilihan_id', 'idx_parameter_penilaian_daftar_pilihan');

            $table->foreign('daftar_pilihan_id', 'fk_parameter_penilaian_daftar_pilihan')
                ->references('id_daftar_pilihan')->on('daftar_pilihan')
                ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parameter_penilaian_sp');
    }
};
