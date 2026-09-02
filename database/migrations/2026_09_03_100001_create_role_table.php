<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 1, tabel `role`.
 *
 * Role dinamis: dibuat & diatur Admin lewat antarmuka. 4 role bawaan ditanam
 * seeder pada Task 3.3 (RBAC), di luar migration ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role', function (Blueprint $table) {
            $table->id('id_role');
            $table->string('nama', 100);
            $table->string('deskripsi', 255)->nullable();
            $table->enum('cakupan_data', ['Semua', 'Per SP', 'Per Bidang']);
            $table->boolean('is_bawaan')->default(false);
            $table->boolean('is_terkunci')->default(false);
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique('nama', 'uq_role_nama');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role');
    }
};
