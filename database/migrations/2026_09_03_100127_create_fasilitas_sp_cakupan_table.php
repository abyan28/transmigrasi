<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 3, pivot `fasilitas_sp_cakupan`.
 *
 * SP yang dilayani sebuah fasilitas (Putaran 7). WAJIB memuat SP pangkal.
 * Pivot murni tanpa model: dipakai lewat `belongsToMany` pada `FasilitasSp`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fasilitas_sp_cakupan', function (Blueprint $table) {
            $table->id('id_fasilitas_sp_cakupan');
            $table->unsignedBigInteger('fasilitas_sp_id');
            $table->unsignedBigInteger('satuan_permukiman_id');
            $table->timestamps();

            $table->unique(['fasilitas_sp_id', 'satuan_permukiman_id'], 'uq_fasilitas_cakupan');
            $table->index('satuan_permukiman_id', 'idx_fasilitas_cakupan_sp');

            $table->foreign('fasilitas_sp_id', 'fk_fasilitas_cakupan_fasilitas')
                ->references('id_fasilitas_sp')->on('fasilitas_sp')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('satuan_permukiman_id', 'fk_fasilitas_cakupan_sp')
                ->references('id_satuan_permukiman')->on('satuan_permukiman')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fasilitas_sp_cakupan');
    }
};
