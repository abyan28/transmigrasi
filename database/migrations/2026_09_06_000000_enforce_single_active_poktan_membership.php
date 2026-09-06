<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu keanggotaan aktif per keluarga, ditegakkan lewat kolom turunan
 * `transmigran_aktif_id` (NULL bila non-Aktif/terhapus) + UNIQUE di atasnya --
 * bukan partial index (MySQL tak punya). Butuh generated column + indeks
 * di atasnya: MariaDB >= 10.2 / SQLite >= 3.31 (suite Feature).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anggota_poktan', function (Blueprint $table) {
            $table->index('poktan_id', 'idx_anggota_poktan_poktan');
        });

        Schema::table('anggota_poktan', function (Blueprint $table) {
            $table->dropUnique('uq_anggota_poktan_poktan_transmigran');
            $table->unsignedBigInteger('transmigran_aktif_id')
                ->nullable()
                ->virtualAs("CASE WHEN status = 'Aktif' AND deleted_at IS NULL THEN transmigran_id END");
            $table->unique('transmigran_aktif_id', 'uq_anggota_poktan_transmigran_aktif');
        });
    }

    public function down(): void
    {
        Schema::table('anggota_poktan', function (Blueprint $table) {
            $table->dropUnique('uq_anggota_poktan_transmigran_aktif');
            $table->dropColumn('transmigran_aktif_id');
            $table->unique(['poktan_id', 'transmigran_id'], 'uq_anggota_poktan_poktan_transmigran');
        });

        Schema::table('anggota_poktan', function (Blueprint $table) {
            $table->dropIndex('idx_anggota_poktan_poktan');
        });
    }
};
