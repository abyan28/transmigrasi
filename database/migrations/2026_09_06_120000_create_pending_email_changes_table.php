<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Maksimum satu permintaan ubah-email HIDUP per pengguna, lewat kolom turunan
 * `pending_user_id` (NULL bila sudah dipakai/dibatalkan) + UNIQUE di atasnya.
 * Butuh generated column + indeks di atasnya: MariaDB >= 10.2 / SQLite >= 3.31
 * (suite Feature).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_email_changes', function (Blueprint $table) {
            $table->id('id_pending_email_change');
            $table->unsignedBigInteger('user_id');
            $table->string('new_email', 255);
            $table->char('token_hash', 64);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('pending_user_id')
                ->nullable()
                ->virtualAs('CASE WHEN used_at IS NULL AND cancelled_at IS NULL THEN user_id END');

            $table->unique('token_hash', 'uq_pending_email_changes_token_hash');
            $table->unique('pending_user_id', 'uq_pending_email_changes_pending_user');
            $table->index('user_id', 'idx_pending_email_changes_user');
            $table->index('new_email', 'idx_pending_email_changes_new_email');
            $table->index('expires_at', 'idx_pending_email_changes_expires');

            $table->foreign('user_id', 'fk_pending_email_changes_user')
                ->references('id_user')->on('user')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_email_changes');
    }
};
