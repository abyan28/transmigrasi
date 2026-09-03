<?php

namespace App\Models;

use App\Models\Scopes\CakupanDataSp;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rute pencapaian ke satu SP (Tabel 2.1 Monografi). 1:N, tanpa soft delete.
 */
#[ScopedBy([CakupanDataSp::class])]
class RuteAksesibilitasSp extends Model
{
    protected $table = 'rute_aksesibilitas_sp';

    protected $primaryKey = 'id_rute_aksesibilitas_sp';

    protected $fillable = [
        'satuan_permukiman_id', 'rute', 'jarak_km', 'sarana_angkutan',
        'tempat_pemberangkatan', 'kondisi_jalan', 'waktu_tempuh', 'ongkos_rp', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'jarak_km' => 'decimal:1',
            'ongkos_rp' => 'decimal:2',
        ];
    }

    public function satuanPermukiman(): BelongsTo
    {
        return $this->belongsTo(SatuanPermukiman::class, 'satuan_permukiman_id', 'id_satuan_permukiman');
    }
}
