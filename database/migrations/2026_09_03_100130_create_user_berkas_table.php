<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 4b, pivot `user_berkas`.
 *
 * Foto profil. Pivot dipakai (bukan FK langsung `user.foto_berkas_id`) untuk
 * memutus siklus: `berkas.user_id` -> `user`. UNIQUE pada `user_id` SAJA
 * (satu foto per pengguna) -- pembeda dari sepuluh pivot `*_berkas` multifile.
 * `peran` DEFAULT 'foto'; tanpa indeks `peran`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_berkas', function (Blueprint $table) {
            $table->id('id_user_berkas');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('berkas_id');
            $table->string('peran', 30)->default('foto');
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->unique('user_id', 'uq_user_berkas');
            $table->index('berkas_id', 'idx_user_berkas_berkas');

            $table->foreign('user_id', 'fk_user_berkas_induk')
                ->references('id_user')->on('user')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('berkas_id', 'fk_user_berkas_berkas')
                ->references('id_berkas')->on('berkas')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_berkas');
    }
};
