<?php

namespace App\Models;

use App\Enums\AksiAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jejak perubahan data penting dan kejadian akun. Hanya kolom yang berubah
 * disimpan (`data_lama`/`data_baru`); kolom `password` WAJIB dikecualikan.
 * Tabel riwayat: TANPA soft delete, tidak pernah disunting.
 *
 * Task 3.2 memakai model ini hanya untuk mencatat Login/Logout/Reset Kata
 * Sandi lewat `AuditLog::create()` langsung. Pencatatan otomatis atas
 * perubahan data (observer + diffing) adalah Task 3.6.
 */
class AuditLog extends Model
{
    protected $table = 'audit_log';

    protected $primaryKey = 'id_audit_log';

    protected $fillable = [
        'user_id', 'aksi', 'nama_tabel', 'record_id',
        'data_lama', 'data_baru', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'aksi' => AksiAuditLog::class,
            'data_lama' => 'array',
            'data_baru' => 'array',
            'record_id' => 'integer',
        ];
    }

    /**
     * Petugas pelaku; NULL bila akunnya kemudian dihapus (FK SET NULL).
     */
    public function pelaku(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id_user');
    }
}
