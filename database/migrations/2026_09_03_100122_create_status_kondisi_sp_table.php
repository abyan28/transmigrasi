<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 4, tabel `status_kondisi_sp`.
 *
 * Ambang + wording predikat kondisi SP, disunting dinas. Jumlah baris tetap 3;
 * `kode` = kunci enum perilaku `StatusKondisiSp::dariSkor`. Tanpa FK, tanpa
 * soft delete.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_kondisi_sp', function (Blueprint $table) {
            $table->id('id_status_kondisi_sp');
            $table->string('kode', 30);
            $table->string('nama', 50);
            $table->string('keterangan', 255)->nullable();
            $table->decimal('ambang_bawah', 5, 2);
            $table->string('warna', 20);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->unique('kode', 'uq_status_kondisi_sp_kode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_kondisi_sp');
    }
};
