<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hasil_panen', function (Blueprint $table): void {
            $table->enum('status', ['Aktif', 'Dibatalkan'])->default('Aktif')->after('keterangan');
            $table->timestamp('dibatalkan_pada')->nullable()->after('status');
            $table->unsignedBigInteger('dibatalkan_oleh')->nullable()->after('dibatalkan_pada');
            $table->text('alasan_pembatalan')->nullable()->after('dibatalkan_oleh');
            $table->index('dibatalkan_oleh', 'idx_hasil_panen_pembatal');
            $table->foreign('dibatalkan_oleh', 'fk_hasil_panen_pembatal')
                ->references('id_user')->on('user')
                ->nullOnDelete()->cascadeOnUpdate();
        });

        DB::table('hasil_panen')->whereNotNull('deleted_at')->update([
            'status' => 'Dibatalkan',
            'dibatalkan_pada' => DB::raw('deleted_at'),
            'alasan_pembatalan' => 'Catatan lama dihapus sebelum fitur pembatalan tersedia.',
        ]);

        Schema::table('hasil_panen', function (Blueprint $table): void {
            $table->dropUnique('uq_hasil_panen_penanaman_aktif');
            $table->dropColumn('penanaman_aktif_id');
        });

        Schema::table('hasil_panen', function (Blueprint $table): void {
            $table->unsignedBigInteger('penanaman_aktif_id')
                ->nullable()
                ->virtualAs("CASE WHEN status = 'Aktif' AND deleted_at IS NULL THEN penanaman_id END");
            $table->unique('penanaman_aktif_id', 'uq_hasil_panen_penanaman_aktif');
        });
    }

    public function down(): void
    {
        $ganda = DB::table('hasil_panen')
            ->whereNull('deleted_at')
            ->select('penanaman_id')
            ->groupBy('penanaman_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($ganda) {
            throw new RuntimeException('Rollback ditolak: ada lebih dari satu riwayat panen untuk satu penanaman. Pulihkan cadangan basis data.');
        }

        Schema::table('hasil_panen', function (Blueprint $table): void {
            $table->dropUnique('uq_hasil_panen_penanaman_aktif');
            $table->dropColumn('penanaman_aktif_id');
        });

        Schema::table('hasil_panen', function (Blueprint $table): void {
            $table->unsignedBigInteger('penanaman_aktif_id')
                ->nullable()
                ->virtualAs('CASE WHEN deleted_at IS NULL THEN penanaman_id END');
            $table->unique('penanaman_aktif_id', 'uq_hasil_panen_penanaman_aktif');
        });

        Schema::table('hasil_panen', function (Blueprint $table): void {
            $table->dropForeign('fk_hasil_panen_pembatal');
            $table->dropIndex('idx_hasil_panen_pembatal');
            $table->dropColumn(['status', 'dibatalkan_pada', 'dibatalkan_oleh', 'alasan_pembatalan']);
        });
    }
};
