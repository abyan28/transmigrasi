<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Konvergensi untuk basis data yang menjalankan migrasi Tahap-3 SEBELUM
 * Putaran 16 menyunting `2026_09_03_100140` / `2026_09_03_100160` (menambah
 * `uq_transmigran_id_sp` dan mengganti FK `lahan` menjadi komposit
 * `fk_lahan_transmigran_sp`). Laravel tidak menjalankan ulang migrasi yang
 * sudah tercatat, sehingga tanpa migrasi ini backstop integritas
 * "SP lahan == SP pemilik" tidak pernah ada pada DB tersebut.
 *
 * IDEMPOTEN: pada `migrate:fresh` (seluruh suite uji, CI, `sim:banding-skema`)
 * struktur sudah dibuat migrasi Tahap-3 yang tersunting -> migrasi ini no-op,
 * paritas `schema.sql` tetap. Hanya bertindak pada MariaDB yang strukturnya
 * masih lama. SQLite (suite Feature) selalu fresh -> dilewati.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $skema = DB::getDatabaseName();

        $adaUnik = DB::selectOne(
            'SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$skema, 'transmigran', 'uq_transmigran_id_sp'],
        );

        if ($adaUnik === null) {
            Schema::table('transmigran', function (Blueprint $table) {
                $table->unique(['id_transmigran', 'satuan_permukiman_id'], 'uq_transmigran_id_sp');
            });
        }

        $adaFkLama = DB::selectOne(
            'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? LIMIT 1',
            [$skema, 'lahan', 'fk_lahan_transmigran'],
        );

        if ($adaFkLama !== null) {
            Schema::table('lahan', function (Blueprint $table) {
                $table->dropForeign('fk_lahan_transmigran');
            });

            $adaIndeks = DB::selectOne(
                'SELECT 1 FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
                [$skema, 'lahan', 'idx_lahan_transmigran_sp'],
            );

            Schema::table('lahan', function (Blueprint $table) use ($adaIndeks) {
                if ($adaIndeks === null) {
                    $table->index(['transmigran_id', 'satuan_permukiman_id'], 'idx_lahan_transmigran_sp');
                }

                $table->foreign(['transmigran_id', 'satuan_permukiman_id'], 'fk_lahan_transmigran_sp')
                    ->references(['id_transmigran', 'satuan_permukiman_id'])->on('transmigran')
                    ->onDelete('cascade')->onUpdate('cascade');
            });
        }
    }

    public function down(): void
    {
        // Konvergensi maju saja: struktur akhir sama dengan yang dibuat
        // migrasi Tahap-3 tersunting, jadi tidak ada yang perlu dibalik di sini.
    }
};
