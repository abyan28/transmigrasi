<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 4, tabel `satuan`.
 *
 * Satuan berat + faktor konversi ke ton (Ton/Kuintal/Kilogram). Referensi
 * murni: tanpa soft delete. Ditarik maju sebelum `komoditas` (topological sort).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('satuan', function (Blueprint $table) {
            $table->id('id_satuan');
            $table->string('nama', 50);
            $table->string('simbol', 10);
            $table->decimal('faktor_ke_ton', 10, 6);
            $table->timestamps();

            $table->unique('nama', 'uq_satuan_nama');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('satuan');
    }
};
