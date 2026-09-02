<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 2, tabel `provinsi`.
 * Referensi wilayah (cabang administratif): tanpa soft delete, dilindungi RESTRICT.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provinsi', function (Blueprint $table) {
            $table->id('id_provinsi');
            $table->string('nama', 100);
            $table->string('kode', 10)->nullable();
            $table->timestamps();

            $table->unique('nama', 'uq_provinsi_nama');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provinsi');
    }
};
