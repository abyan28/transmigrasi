<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alsintan_distribusi', function (Blueprint $table) {
            $table->unique(['alsintan_id', 'poktan_id'], 'uq_alsintan_distribusi_alsintan_poktan');
        });

        Schema::table('saprotan_distribusi', function (Blueprint $table) {
            $table->unique(['saprotan_id', 'poktan_id'], 'uq_saprotan_distribusi_saprotan_poktan');
        });
    }

    public function down(): void
    {
        Schema::table('alsintan_distribusi', function (Blueprint $table) {
            $table->dropUnique('uq_alsintan_distribusi_alsintan_poktan');
        });

        Schema::table('saprotan_distribusi', function (Blueprint $table) {
            $table->dropUnique('uq_saprotan_distribusi_saprotan_poktan');
        });
    }
};
