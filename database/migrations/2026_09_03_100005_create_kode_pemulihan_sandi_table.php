<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 1, tabel `kode_pemulihan_sandi`.
 *
 * Kode verifikasi 6 digit disimpan sebagai SIDIK (`kode_hash`), bukan angkanya
 * (`rules.md` 14b). Menggantikan `password_reset_tokens` bawaan. Hanya
 * `created_at` (dasar batas 3 permintaan/jam) -- tanpa `updated_at`, tanpa
 * soft delete: tabel riwayat singkat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kode_pemulihan_sandi', function (Blueprint $table) {
            $table->id('id_kode_pemulihan');
            $table->unsignedBigInteger('user_id');
            $table->string('kode_hash', 255);
            $table->timestamp('kedaluwarsa_pada')->useCurrent();
            $table->unsignedTinyInteger('percobaan')->default(0);
            $table->timestamp('dipakai_pada')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id', 'idx_kode_pemulihan_user');
            $table->index('kedaluwarsa_pada', 'idx_kode_pemulihan_kedaluwarsa');
            $table->index('created_at', 'idx_kode_pemulihan_created');

            $table->foreign('user_id', 'fk_kode_pemulihan_user')
                ->references('id_user')->on('user')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kode_pemulihan_sandi');
    }
};
