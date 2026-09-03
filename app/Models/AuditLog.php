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
 * Kejadian akun (Login/Logout/Reset Kata Sandi/Nonaktifkan/Aktifkan/Ubah Izin
 * Role) dicatat manual lewat `AuditLog::create()` di controllernya karena
 * butuh konteks tambahan. Perubahan DATA (Tambah/Ubah/Hapus/Pulihkan pada 32
 * model) dicatat otomatis oleh `App\Observers\AuditLogObserver` (Task 3.6),
 * hanya kolom yang berubah, `password` selalu dikecualikan.
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
