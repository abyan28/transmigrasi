<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 2, pivot `user_satuan_permukiman`.
 *
 * Penugasan SP; hanya bermakna bagi role bercakupan `Per SP` (`rules.md`
 * 5.0b-1 poin 9). Pivot murni -- tanpa model tersendiri.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_satuan_permukiman', function (Blueprint $table) {
            $table->id('id_user_satuan_permukiman');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('satuan_permukiman_id');
            $table->timestamps();

            $table->unique(['user_id', 'satuan_permukiman_id'], 'uq_user_sp');
            $table->index('satuan_permukiman_id', 'idx_user_sp_sp');

            $table->foreign('user_id', 'fk_user_sp_user')
                ->references('id_user')->on('user')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('satuan_permukiman_id', 'fk_user_sp_sp')
                ->references('id_satuan_permukiman')->on('satuan_permukiman')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_satuan_permukiman');
    }
};
