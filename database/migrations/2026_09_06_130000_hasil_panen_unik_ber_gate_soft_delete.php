<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menyelaraskan `hasil_panen` dengan pola "satu baris HIDUP per induk" yang
 * dipakai `anggota_poktan` (`uq_anggota_poktan_transmigran_aktif`) dan
 * `pending_email_changes` (`uq_pending_email_changes_pending_user`).
 *
 * Migrasi `2026_09_06_000001` sebelumnya membuat `uq_hasil_panen_penanaman`
 * sebagai UNIQUE polos atas `penanaman_id`. Karena `hasil_panen` ber-soft
 * delete, baris yang salah lalu dihapus halus TETAP menahan slot unik,
 * sehingga panen yang benar tidak pernah dapat dicatat lewat antarmuka
 * (tidak ada alur restore). Migrasi ini menggantinya dengan kolom turunan
 * ber-gate `deleted_at`: baris soft-deleted bernilai NULL dan tidak lagi
 * menahan slot. Dua baris HIDUP untuk satu penanaman tetap mustahil.
 *
 * Migrasi TERPISAH (bukan menyunting `2026_09_06_000001`) supaya basis data
 * yang sudah menjalankan migrasi itu ikut konvergen tanpa `migrate:fresh`.
 *
 * Butuh generated column + indeks di atasnya: MariaDB >= 10.2 / SQLite >= 3.31.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hasil_panen', function (Blueprint $table) {
            // Kembalikan indeks non-unik lebih dulu supaya FK
            // `fk_hasil_panen_penanaman` tidak pernah tanpa indeks penyangga.
            $table->index('penanaman_id', 'idx_hasil_panen_penanaman');
            $table->dropUnique('uq_hasil_panen_penanaman');
        });

        Schema::table('hasil_panen', function (Blueprint $table) {
            $table->unsignedBigInteger('penanaman_aktif_id')
                ->nullable()
                ->virtualAs('CASE WHEN deleted_at IS NULL THEN penanaman_id END');
            $table->unique('penanaman_aktif_id', 'uq_hasil_panen_penanaman_aktif');
        });
    }

    public function down(): void
    {
        Schema::table('hasil_panen', function (Blueprint $table) {
            $table->dropUnique('uq_hasil_panen_penanaman_aktif');
            $table->dropColumn('penanaman_aktif_id');
        });

        Schema::table('hasil_panen', function (Blueprint $table) {
            $table->unique('penanaman_id', 'uq_hasil_panen_penanaman');
            $table->dropIndex('idx_hasil_panen_penanaman');
        });
    }
};
