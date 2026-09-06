<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hasil_panen', function (Blueprint $table) {
            $table->unique('penanaman_id', 'uq_hasil_panen_penanaman');
            $table->dropIndex('idx_hasil_panen_penanaman');
        });
    }

    public function down(): void
    {
        Schema::table('hasil_panen', function (Blueprint $table) {
            $table->index('penanaman_id', 'idx_hasil_panen_penanaman');
            $table->dropUnique('uq_hasil_panen_penanaman');
        });
    }
};
