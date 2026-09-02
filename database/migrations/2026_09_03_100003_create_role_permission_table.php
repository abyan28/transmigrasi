<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terjemahan `database/data/schema.sql` -- DOMAIN 1, pivot `role_permission` (N:M).
 *
 * Pivot murni: tanpa model Eloquent tersendiri, dibaca lewat `Role::permissions()`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permission', function (Blueprint $table) {
            $table->id('id_role_permission');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamps();

            $table->unique(['role_id', 'permission_id'], 'uq_role_permission');
            $table->index('permission_id', 'idx_role_permission_permission');

            $table->foreign('role_id', 'fk_role_permission_role')
                ->references('id_role')->on('role')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('permission_id', 'fk_role_permission_permission')
                ->references('id_permission')->on('permission')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permission');
    }
};
