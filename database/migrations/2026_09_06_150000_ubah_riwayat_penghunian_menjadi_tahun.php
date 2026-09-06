<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('riwayat_penghunian', function (Blueprint $table) {
            $table->unsignedSmallInteger('tahun_mulai_menghuni')->nullable()->after('transmigran_id');
            $table->unsignedSmallInteger('tahun_selesai_menghuni')->nullable()->after('tahun_mulai_menghuni');
        });

        DB::table('riwayat_penghunian')->orderBy('id_riwayat_penghunian')->get()
            ->each(function ($baris): void {
                DB::table('riwayat_penghunian')
                    ->where('id_riwayat_penghunian', $baris->id_riwayat_penghunian)
                    ->update([
                        'tahun_mulai_menghuni' => (int) substr((string) $baris->tanggal_masuk, 0, 4),
                        'tahun_selesai_menghuni' => $baris->tanggal_keluar === null
                            ? null
                            : (int) substr((string) $baris->tanggal_keluar, 0, 4),
                    ]);
            });

        Schema::table('riwayat_penghunian', function (Blueprint $table) {
            $table->dropIndex('idx_riwayat_penghunian_masuk');
            $table->dropIndex('idx_riwayat_penghunian_keluar');
            $table->dropColumn(['tanggal_masuk', 'tanggal_keluar']);
        });

        Schema::table('riwayat_penghunian', function (Blueprint $table) {
            $table->unsignedSmallInteger('tahun_mulai_menghuni')->nullable(false)->change();
            $table->index('tahun_mulai_menghuni', 'idx_riwayat_penghunian_mulai');
            $table->index('tahun_selesai_menghuni', 'idx_riwayat_penghunian_selesai');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Presisi tanggal lama tidak dapat dipulihkan dari data tahun. Pulihkan cadangan basis data untuk rollback.');
    }
};
