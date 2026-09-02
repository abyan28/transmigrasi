<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menggantikan migration `create_users_table` bawaan Laravel.
 *
 * Tabel `users` bawaan (PK `id`, kolom `name`) digantikan tabel `user`
 * (PK `id_user`) pada migration Domain 1. Tabel `password_reset_tokens` bawaan
 * TIDAK dibuat: pemulihan kata sandi memakai `kode_pemulihan_sandi` dan
 * `rules.md` Â§14b melarang rute pemulihan sandi bawaan.
 *
 * Hanya `sessions` yang dipertahankan dari migration bawaan, sesuai
 * `database/data/schema.sql`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity');

            $table->index('user_id', 'idx_sessions_user_id');
            $table->index('last_activity', 'idx_sessions_last_activity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
