<?php

namespace App\Models;

use App\Enums\JenisNotifikasi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class Notifikasi extends Model
{
    protected $table = 'notifikasi';

    protected $primaryKey = 'id_notifikasi';

    protected $fillable = [
        'user_id', 'jenis', 'pengaduan_id', 'satuan_permukiman_id',
        'infrastruktur_id', 'subjek_user_id', 'pesan', 'dibaca_at',
    ];

    protected function casts(): array
    {
        return [
            'jenis' => JenisNotifikasi::class,
            'dibaca_at' => 'datetime',
        ];
    }

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id_user');
    }

    public function pengaduan(): BelongsTo
    {
        return $this->belongsTo(Pengaduan::class, 'pengaduan_id', 'id_pengaduan');
    }

    public function satuanPermukiman(): BelongsTo
    {
        return $this->belongsTo(SatuanPermukiman::class, 'satuan_permukiman_id', 'id_satuan_permukiman');
    }

    public function infrastruktur(): BelongsTo
    {
        return $this->belongsTo(Infrastruktur::class, 'infrastruktur_id', 'id_infrastruktur');
    }

    public function subjekPengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subjek_user_id', 'id_user');
    }

    public static function kirim(
        JenisNotifikasi $jenis,
        Collection $penerima,
        array $subjek,
        string $pesan,
    ): void {
        $waktu = now();

        foreach ($penerima as $user) {
            $kunci = ['user_id' => $user->id_user, 'jenis' => $jenis->value, ...$subjek];

            if (static::query()->where($kunci)->whereNull('dibaca_at')->exists()) {
                continue;
            }

            static::create($kunci + ['pesan' => $pesan, 'created_at' => $waktu, 'updated_at' => $waktu]);
        }
    }

    public function urlTujuan(): string
    {
        return match ($this->jenis) {
            JenisNotifikasi::PengaduanBaru,
            JenisNotifikasi::PengaduanMendesak => route('pengaduan.detail', $this->pengaduan_id),
            JenisNotifikasi::SpPerluPenanganan => route('sp.detail', $this->satuan_permukiman_id),
            JenisNotifikasi::InfrastrukturRusakBerat => route('infrastruktur.detail', $this->infrastruktur_id),
            JenisNotifikasi::AkunPengguna => route('pengguna.index'),
        };
    }
}
