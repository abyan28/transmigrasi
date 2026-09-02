<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 2, tabel `rute_aksesibilitas_sp`.
 *
 * Rute pencapaian ke satu SP (Tabel 2.1 Monografi). 1:N, tanpa tabel riwayat,
 * tanpa soft delete. CASCADE saat SP dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rute_aksesibilitas_sp', function (Blueprint $table) {
            $table->id('id_rute_aksesibilitas_sp');
            $table->unsignedBigInteger('satuan_permukiman_id');
            $table->string('rute', 255);
            $table->decimal('jarak_km', 7, 1)->nullable();
            $table->string('sarana_angkutan', 150)->nullable();
            $table->string('tempat_pemberangkatan', 150)->nullable();
            $table->string('kondisi_jalan', 150)->nullable();
            $table->string('waktu_tempuh', 80)->nullable();
            $table->decimal('ongkos_rp', 12, 2)->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();

            $table->index('satuan_permukiman_id', 'idx_rute_sp');

            $table->foreign('satuan_permukiman_id', 'fk_rute_sp')
                ->references('id_satuan_permukiman')->on('satuan_permukiman')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rute_aksesibilitas_sp');
    }
};
