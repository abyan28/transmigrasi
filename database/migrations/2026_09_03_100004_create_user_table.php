<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 1, tabel `user`.
 *
 * Menggantikan tabel `users` bawaan Laravel. Seluruh pengguna adalah petugas
 * (warga tidak punya akun). Login memakai email ATAU username pada satu isian
 * (`rules.md` 14b poin 4). Tanpa `email_verified_at` -- akun dibuat Admin,
 * bukan pendaftaran mandiri.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user', function (Blueprint $table) {
            $table->id('id_user');
            $table->unsignedBigInteger('role_id');
            $table->string('nama', 255);
            $table->string('username', 50);
            $table->string('email', 255);
            $table->string('password', 255);
            $table->boolean('password_harus_diganti')->default(false);
            $table->string('telepon', 20)->nullable();
            $table->string('jabatan', 100)->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('username', 'uq_user_username');
            $table->unique('email', 'uq_user_email');
            $table->index('role_id', 'idx_user_role');
            $table->index('is_aktif', 'idx_user_is_aktif');

            $table->foreign('role_id', 'fk_user_role')
                ->references('id_role')->on('role')
                ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user');
    }
};
