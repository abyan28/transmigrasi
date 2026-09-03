<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 9, pivot `infrastruktur_sp`.
 *
 * Cakupan layanan lintas SP (Putaran 7). WAJIB memuat SP pangkal. Pivot murni
 * tanpa model. CASCADE ke kedua induk.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('infrastruktur_sp', function (Blueprint $table) {
            $table->id('id_infrastruktur_sp');
            $table->unsignedBigInteger('infrastruktur_id');
            $table->unsignedBigInteger('satuan_permukiman_id');
            $table->timestamps();

            $table->unique(['infrastruktur_id', 'satuan_permukiman_id'], 'uq_infrastruktur_sp');
            $table->index('satuan_permukiman_id', 'idx_infrastruktur_sp_sp');

            $table->foreign('infrastruktur_id', 'fk_infrastruktur_sp_infrastruktur')
                ->references('id_infrastruktur')->on('infrastruktur')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('satuan_permukiman_id', 'fk_infrastruktur_sp_sp')
                ->references('id_satuan_permukiman')->on('satuan_permukiman')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('infrastruktur_sp');
    }
};
