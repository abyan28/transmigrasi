<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 1, tabel `permission`.
 *
 * Kewenangan baku ditanam seeder (Task 3.3); Admin tidak dapat menambah atau
 * menghapus, sebab tiap kewenangan wajib punya pemeriksa di dalam kode.
 * Tanpa soft delete: bukan data operasional.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission', function (Blueprint $table) {
            $table->id('id_permission');
            $table->string('nama', 100);
            $table->string('modul', 50);
            $table->enum('aksi', ['lihat', 'tambah', 'ubah', 'hapus']);
            $table->string('label', 150);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->unique('nama', 'uq_permission_nama');
            $table->index('modul', 'idx_permission_modul');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission');
    }
};
