<?php

namespace App\Models;

use App\Enums\BentukWilayah;
use App\Enums\PolaPermukiman;
use App\Enums\TingkatKesuburanTanah;
use App\Models\Concerns\BerslugOtomatis;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Lokus utama sistem. Titik temu cabang administratif (`desa_id`) dan program
 * (`kawasan_id`), keduanya WAJIB (`rules.md` 4a.3). `kecamatan_id` TIDAK
 * disimpan -- dibaca lewat desa.
 *
 * SELURUH data operasional menaut ke sini, tidak pernah langsung ke desa/kawasan
 * (`rules.md` 7.0). Global scope cakupan data `Per SP` menempel pada model ini
 * (Task 3.4). Relasi ke transmigran/rumah/poktan/lahan/infrastruktur/pengaduan
 * ditambahkan pada batch masing-masing.
 *
 * Pengenal publik URL: `slug`.
 */
class SatuanPermukiman extends Model
{
    use BerslugOtomatis;
    use SoftDeletes;

    protected $table = 'satuan_permukiman';

    protected $primaryKey = 'id_satuan_permukiman';

    protected $fillable = [
        'kawasan_id', 'desa_id', 'user_id', 'nama', 'slug', 'kode_sp',
        'tahun_penempatan', 'luas_lahan', 'jumlah_kk_rencana', 'lintang', 'bujur',
        'berkas_id', 'keterangan',
        'lintang_utara', 'lintang_selatan', 'bujur_barat', 'bujur_timur',
        'jarak_ke_kecamatan_km', 'jarak_ke_kabupaten_km', 'jarak_ke_provinsi_km',
        'batas_utara', 'batas_timur', 'batas_selatan', 'batas_barat',
        'nomor_sk_pencadangan', 'tanggal_sk_pencadangan',
        'pola_permukiman', 'tingkat_kesuburan_tanah', 'ph_tanah_min', 'ph_tanah_maks',
        'bentuk_wilayah', 'kemiringan_min_persen', 'kemiringan_maks_persen',
        'curah_hujan_tahunan_mm', 'curah_hujan_bulan_min_mm', 'curah_hujan_bulan_maks_mm',
        'suhu_min_c', 'suhu_maks_c', 'suhu_rata_c',
        'angin_min_knot', 'angin_maks_knot', 'angin_rata_knot',
        'penyinaran_min_persen', 'penyinaran_maks_persen', 'penyinaran_rata_persen',
        'sumber_air_bersih', 'sumber_air_pertanian',
    ];

    protected function casts(): array
    {
        return [
            'pola_permukiman' => PolaPermukiman::class,
            'tingkat_kesuburan_tanah' => TingkatKesuburanTanah::class,
            'bentuk_wilayah' => BentukWilayah::class,
            'tanggal_sk_pencadangan' => 'date',
            'luas_lahan' => 'decimal:2',
            'jumlah_kk_rencana' => 'integer',
            'lintang' => 'decimal:7',
            'bujur' => 'decimal:7',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function kawasan(): BelongsTo
    {
        return $this->belongsTo(KawasanTransmigrasi::class, 'kawasan_id', 'id_kawasan_transmigrasi');
    }

    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class, 'desa_id', 'id_desa');
    }

    /**
     * Penanggung jawab data SP (opsional).
     */
    public function penanggungJawab(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id_user');
    }

    /**
     * Dokumen SK penetapan SP (FK langsung tunggal, bukan pivot).
     */
    public function berkas(): BelongsTo
    {
        return $this->belongsTo(Berkas::class, 'berkas_id', 'id_berkas');
    }

    public function ruteAksesibilitas(): HasMany
    {
        return $this->hasMany(RuteAksesibilitasSp::class, 'satuan_permukiman_id', 'id_satuan_permukiman');
    }

    public function inventaris(): HasMany
    {
        return $this->hasMany(InventarisSp::class, 'satuan_permukiman_id', 'id_satuan_permukiman');
    }

    /**
     * Fasilitas yang BERDIRI di SP ini (pangkal). Fasilitas yang MELAYANI SP ini
     * tanpa berpangkal di sini dibaca lewat pivot `fasilitas_sp_cakupan`.
     */
    public function fasilitas(): HasMany
    {
        return $this->hasMany(FasilitasSp::class, 'satuan_permukiman_id', 'id_satuan_permukiman');
    }

    public function penilaian(): HasMany
    {
        return $this->hasMany(PenilaianSp::class, 'satuan_permukiman_id', 'id_satuan_permukiman');
    }

    public function transmigran(): HasMany
    {
        return $this->hasMany(Transmigran::class, 'satuan_permukiman_id', 'id_satuan_permukiman');
    }

    public function rumah(): HasMany
    {
        return $this->hasMany(Rumah::class, 'satuan_permukiman_id', 'id_satuan_permukiman');
    }

    public function poktan(): HasMany
    {
        return $this->hasMany(Poktan::class, 'satuan_permukiman_id', 'id_satuan_permukiman');
    }

    public function lahan(): HasMany
    {
        return $this->hasMany(Lahan::class, 'satuan_permukiman_id', 'id_satuan_permukiman');
    }

    /**
     * Infrastruktur yang BERPANGKAL di SP ini.
     */
    public function infrastruktur(): HasMany
    {
        return $this->hasMany(Infrastruktur::class, 'satuan_permukiman_id', 'id_satuan_permukiman');
    }

    public function pengaduan(): HasMany
    {
        return $this->hasMany(Pengaduan::class, 'satuan_permukiman_id', 'id_satuan_permukiman');
    }

    /**
     * Petugas yang ditugaskan ke SP ini (cakupan data `Per SP`).
     */
    public function petugas(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_satuan_permukiman',
            'satuan_permukiman_id',
            'user_id',
            'id_satuan_permukiman',
            'id_user',
        )->withTimestamps();
    }
}
