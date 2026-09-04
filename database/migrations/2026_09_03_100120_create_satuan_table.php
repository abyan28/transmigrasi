<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 4, tabel `satuan`.
 *
 * Satuan jumlah + faktor konversi ke ton. Satuan berat (Ton/Kuintal/Kilogram)
 * ber-`faktor_ke_ton`; satuan non-berat (Liter, Rol untuk saprotan) ber-NULL.
 * Referensi murni: tanpa soft delete. Ditarik maju sebelum `komoditas`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('satuan', function (Blueprint $table) {
            $table->id('id_satuan');
            $table->string('nama', 50);
            $table->string('simbol', 10);
            $table->decimal('faktor_ke_ton', 10, 6)->nullable();
            $table->timestamps();

            $table->unique('nama', 'uq_satuan_nama');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('satuan');
    }
};
