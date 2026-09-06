<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('transmigran', 'status_anggota_poktan')) {
            Schema::table('transmigran', fn (Blueprint $table) => $table->dropColumn('status_anggota_poktan'));
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('transmigran', 'status_anggota_poktan')) {
            Schema::table('transmigran', fn (Blueprint $table) => $table
                ->enum('status_anggota_poktan', ['Ya', 'Tidak'])
                ->default('Tidak')
                ->after('tahun_keluar'));
        }
    }
};
