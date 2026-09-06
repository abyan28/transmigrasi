<?php

namespace App\Models;

use App\Enums\AsalWakilPoktan;
use App\Enums\StatusKeaktifanAnggota;
use App\Models\Concerns\DisaringLewatInduk;
use App\Models\Scopes\CakupanDataSp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

/**
 * Keanggotaan poktan. `transmigran_id` menunjuk KELUARGA yang diwakili;
 * `asal_wakil` memuat 3 nilai (satu tipe dengan `poktan.asal_ketua`), tetapi
 * 'Bukan Transmigran' tidak berlaku di sini (ditegakkan aplikasi). `jabatan` =
 * VARCHAR REF, TANPA 'Ketua'. Anggota berhenti ditandai 'Sudah Keluar', tidak dihapus.
 */
class AnggotaPoktan extends Model
{
    use DisaringLewatInduk;
    use SoftDeletes;

    protected static string $indukCakupan = 'poktan';

    protected $table = 'anggota_poktan';

    protected $primaryKey = 'id_anggota_poktan';

    protected $fillable = [
        'poktan_id', 'transmigran_id', 'asal_wakil', 'anggota_keluarga_id',
        'telepon_wakil', 'jabatan', 'tanggal_masuk', 'status', 'tanggal_keluar',
        'alasan_keluar', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'asal_wakil' => AsalWakilPoktan::class,
            'tanggal_masuk' => 'date',
            'status' => StatusKeaktifanAnggota::class,
            'tanggal_keluar' => 'date',
        ];
    }

    public static function pastikanSah(int|Poktan $target, array $data, ?int $abaikanId = null, string $awalanGalat = ''): void
    {
        CakupanDataSp::pastikanDapatDitulis($target);

        $spId = $target instanceof Poktan ? $target->satuan_permukiman_id : $target;
        $transmigranId = (int) $data['transmigran_id'];
        $transmigran = Transmigran::find($transmigranId);

        if (($data['status'] ?? null) === StatusKeaktifanAnggota::Aktif->value
            && ($transmigran === null || $transmigran->satuan_permukiman_id !== $spId)) {
            throw ValidationException::withMessages([
                $awalanGalat.'transmigran_id' => 'Keluarga aktif harus berasal dari satuan permukiman kelompok tani.',
            ]);
        }

        if (($data['asal_wakil'] ?? null) === AsalWakilPoktan::AnggotaKeluarga->value) {
            $wakil = AnggotaKeluarga::find((int) ($data['anggota_keluarga_id'] ?? 0));

            if ($wakil === null || $wakil->transmigran_id !== $transmigranId) {
                throw ValidationException::withMessages([
                    $awalanGalat.'anggota_keluarga_id' => 'Anggota keluarga yang dipilih bukan bagian dari keluarga transmigran ini.',
                ]);
            }
        }

        if (($data['status'] ?? null) === StatusKeaktifanAnggota::Aktif->value
            && static::withoutGlobalScope('cakupanViaInduk')->where('transmigran_id', $transmigranId)
                ->where('status', StatusKeaktifanAnggota::Aktif->value)
                ->when($abaikanId !== null, fn ($q) => $q->whereKeyNot($abaikanId))
                ->exists()) {
            throw ValidationException::withMessages([
                $awalanGalat.'transmigran_id' => 'Keluarga ini masih berstatus Aktif pada kelompok tani lain. Tandai keluar dari sana lebih dulu.',
            ]);
        }
    }

    public function poktan(): BelongsTo
    {
        return $this->belongsTo(Poktan::class, 'poktan_id', 'id_poktan');
    }

    /**
     * Keluarga yang diwakili anggota ini.
     */
    public function transmigran(): BelongsTo
    {
        return $this->belongsTo(Transmigran::class, 'transmigran_id', 'id_transmigran');
    }

    /**
     * Anggota keluarga yang mewakili; hanya bila `asal_wakil = Anggota Keluarga`.
     */
    public function anggotaKeluarga(): BelongsTo
    {
        return $this->belongsTo(AnggotaKeluarga::class, 'anggota_keluarga_id', 'id_anggota_keluarga');
    }
}
