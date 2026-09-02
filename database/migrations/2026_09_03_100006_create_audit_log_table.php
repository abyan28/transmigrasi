<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 1, tabel `audit_log`.
 *
 * Riwayat perubahan data penting. Hanya kolom yang berubah disimpan
 * (`data_lama`/`data_baru` JSON); kolom `password` WAJIB dikecualikan.
 * Tabel riwayat: tanpa soft delete.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id('id_audit_log');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('aksi', [
                'Tambah', 'Ubah', 'Hapus', 'Pulihkan', 'Login', 'Logout',
                'Reset Kata Sandi', 'Nonaktifkan Akun', 'Aktifkan Akun', 'Ubah Izin Role',
            ]);
            $table->string('nama_tabel', 64);
            $table->unsignedBigInteger('record_id');
            $table->json('data_lama')->nullable();
            $table->json('data_baru')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->index('user_id', 'idx_audit_log_user');
            $table->index('nama_tabel', 'idx_audit_log_tabel');
            $table->index('created_at', 'idx_audit_log_created');

            $table->foreign('user_id', 'fk_audit_log_user')
                ->references('id_user')->on('user')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
