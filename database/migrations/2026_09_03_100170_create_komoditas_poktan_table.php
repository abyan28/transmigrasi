<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 8, pivot M:N `komoditas_poktan`
 * (komoditas yang diusahakan sebuah poktan). Pivot murni tanpa model.
 * CASCADE ke kedua induk.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('komoditas_poktan', function (Blueprint $table) {
            $table->id('id_komoditas_poktan');
            $table->unsignedBigInteger('poktan_id');
            $table->unsignedBigInteger('komoditas_id');
            $table->timestamps();

            $table->unique(['poktan_id', 'komoditas_id'], 'uq_komoditas_poktan');
            $table->index('komoditas_id', 'idx_komoditas_poktan_komoditas');

            $table->foreign('poktan_id', 'fk_komoditas_poktan_poktan')
                ->references('id_poktan')->on('poktan')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('komoditas_id', 'fk_komoditas_poktan_komoditas')
                ->references('id_komoditas')->on('komoditas')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('komoditas_poktan');
    }
};
