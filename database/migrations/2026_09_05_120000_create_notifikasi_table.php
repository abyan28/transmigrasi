<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id('id_notifikasi');
            $table->unsignedBigInteger('user_id');
            $table->enum('jenis', [
                'Pengaduan Baru',
                'Pengaduan Mendesak',
                'SP Perlu Penanganan',
                'Infrastruktur Rusak Berat',
                'Akun Pengguna',
            ]);
            $table->unsignedBigInteger('pengaduan_id')->nullable();
            $table->unsignedBigInteger('satuan_permukiman_id')->nullable();
            $table->unsignedBigInteger('infrastruktur_id')->nullable();
            $table->unsignedBigInteger('subjek_user_id')->nullable();
            $table->string('pesan');
            $table->timestamp('dibaca_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'dibaca_at'], 'idx_notifikasi_user_dibaca');
            $table->index(['jenis', 'pengaduan_id'], 'idx_notifikasi_jenis_pengaduan');
            $table->index(['jenis', 'satuan_permukiman_id'], 'idx_notifikasi_jenis_sp');
            $table->index(['jenis', 'infrastruktur_id'], 'idx_notifikasi_jenis_infrastruktur');

            $table->foreign('user_id', 'fk_notifikasi_user')
                ->references('id_user')->on('user')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('pengaduan_id', 'fk_notifikasi_pengaduan')
                ->references('id_pengaduan')->on('pengaduan')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('satuan_permukiman_id', 'fk_notifikasi_sp')
                ->references('id_satuan_permukiman')->on('satuan_permukiman')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('infrastruktur_id', 'fk_notifikasi_infrastruktur')
                ->references('id_infrastruktur')->on('infrastruktur')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('subjek_user_id', 'fk_notifikasi_subjek_user')
                ->references('id_user')->on('user')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
    }
};
