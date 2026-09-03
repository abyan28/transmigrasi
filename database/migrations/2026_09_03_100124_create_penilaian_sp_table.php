<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 4, tabel `penilaian_sp`.
 *
 * Riwayat penilaian kondisi SP. Satu SP banyak baris; `rincian` = salinan
 * bobot/kondisi/nilai yang berlaku saat penilaian dibuat. Tabel riwayat:
 * tanpa soft delete. FK SP RESTRICT (jejak tak boleh yatim), user SET NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penilaian_sp', function (Blueprint $table) {
            $table->id('id_penilaian_sp');
            $table->unsignedBigInteger('satuan_permukiman_id');
            $table->date('tanggal_penilaian');
            $table->decimal('skor', 5, 2);
            $table->enum('status', ['Mandiri', 'Berkembang', 'Perlu Penanganan']);
            $table->boolean('ada_primer_nol');
            $table->json('rincian');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('satuan_permukiman_id', 'idx_penilaian_sp_sp');
            $table->index('tanggal_penilaian', 'idx_penilaian_sp_tanggal');
            $table->index('status', 'idx_penilaian_sp_status');
            $table->index('user_id', 'idx_penilaian_sp_user');

            $table->foreign('satuan_permukiman_id', 'fk_penilaian_sp_sp')
                ->references('id_satuan_permukiman')->on('satuan_permukiman')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('user_id', 'fk_penilaian_sp_user')
                ->references('id_user')->on('user')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penilaian_sp');
    }
};
