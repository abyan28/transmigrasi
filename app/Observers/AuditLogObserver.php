<?php

namespace App\Observers;

use App\Enums\AksiAuditLog;
use App\Models\Alsintan;
use App\Models\AlsintanDistribusi;
use App\Models\AnggotaKeluarga;
use App\Models\AnggotaPoktan;
use App\Models\AuditLog;
use App\Models\DaftarPilihan;
use App\Models\Desa;
use App\Models\FasilitasSp;
use App\Models\HasilPanen;
use App\Models\Infrastruktur;
use App\Models\InventarisSp;
use App\Models\Kabupaten;
use App\Models\KawasanTransmigrasi;
use App\Models\Kecamatan;
use App\Models\Komoditas;
use App\Models\Lahan;
use App\Models\ParameterPenilaianSp;
use App\Models\Penanaman;
use App\Models\PenangananPengaduan;
use App\Models\Pengaduan;
use App\Models\PenilaianSp;
use App\Models\Poktan;
use App\Models\Provinsi;
use App\Models\RiwayatKepalaKeluarga;
use App\Models\RiwayatPenghunian;
use App\Models\Rumah;
use App\Models\RuteAksesibilitasSp;
use App\Models\Saprotan;
use App\Models\SaprotanDistribusi;
use App\Models\Satuan;
use App\Models\SatuanPermukiman;
use App\Models\StatusKondisiSp;
use App\Models\Transmigran;
use App\Providers\AppServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Pencatatan otomatis perubahan DATA ke `audit_log` (Task 3.6,
 * `rules.md` §14 poin 5 / `data-dictionary.md` 2.2).
 *
 * Didaftarkan lewat perulangan di `AppServiceProvider::boot()` atas daftar
 * `AuditLogObserver::MODEL` -- 32 model data tidak perlu disunting satu per
 * satu.
 *
 * Yang DICATAT hanya kolom yang benar-benar berubah (bukan seluruh baris),
 * dan `password` / `remember_token` WAJIB dikecualikan. Kejadian akun
 * (Login/Logout/Reset Kata Sandi/Nonaktifkan/Aktifkan/Ubah Izin Role) TIDAK
 * lewat sini -- ia dicatat manual di controllernya karena butuh konteks
 * tambahan (jalur, nama role, jumlah izin).
 *
 * @see AppServiceProvider::daftarkanAuditOtomatis()
 */
class AuditLogObserver
{
    /**
     * Kolom yang tidak pernah masuk `data_lama`/`data_baru`:
     * - `password` / `remember_token`: rahasia (`data-dictionary.md` 2.2).
     * - stempel waktu: derau tanpa nilai telusur.
     * - `deleted_at`: supaya `restore()` -- yang memicu `updated`
     *   (deleted_at -> null) -- tidak menghasilkan baris "Ubah" hantu di
     *   samping baris "Pulihkan".
     *
     * @var array<int, string>
     */
    private const DIKECUALIKAN = [
        'password', 'remember_token', 'created_at', 'updated_at', 'deleted_at',
    ];

    /**
     * Model yang perubahannya dicatat otomatis. Data operasional + master
     * wilayah/SP/pertanian + daftar pilihan. TIDAK termasuk `User`/`Role`/
     * `Permission` (dicatat manual dengan konteks), tabel riwayat sistem
     * (`audit_log`, `kode_pemulihan_sandi`), `Berkas`, dan tabel pivot.
     *
     * @var array<int, class-string<Model>>
     */
    public const MODEL = [
        Provinsi::class,
        Kabupaten::class,
        Kecamatan::class,
        Desa::class,
        KawasanTransmigrasi::class,
        SatuanPermukiman::class,
        RuteAksesibilitasSp::class,
        Satuan::class,
        Komoditas::class,
        StatusKondisiSp::class,
        ParameterPenilaianSp::class,
        PenilaianSp::class,
        InventarisSp::class,
        FasilitasSp::class,
        DaftarPilihan::class,
        Transmigran::class,
        AnggotaKeluarga::class,
        RiwayatKepalaKeluarga::class,
        RiwayatPenghunian::class,
        Rumah::class,
        Lahan::class,
        Poktan::class,
        AnggotaPoktan::class,
        Alsintan::class,
        AlsintanDistribusi::class,
        Saprotan::class,
        SaprotanDistribusi::class,
        Penanaman::class,
        HasilPanen::class,
        Infrastruktur::class,
        Pengaduan::class,
        PenangananPengaduan::class,
    ];

    public function created(Model $model): void
    {
        $this->rekam($model, AksiAuditLog::Tambah, null, $this->saring($model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        $baru = $this->saring($model->getChanges());

        // Hanya stempel waktu / deleted_at yang berubah -> bukan suntingan data.
        if ($baru === []) {
            return;
        }

        $lama = array_intersect_key($this->saring($model->getOriginal()), $baru);

        $this->rekam($model, AksiAuditLog::Ubah, $lama, $baru);
    }

    public function deleted(Model $model): void
    {
        // Soft delete maupun force delete: enum hanya punya `Hapus`.
        $this->rekam($model, AksiAuditLog::Hapus, $this->saring($model->getOriginal()), null);
    }

    public function restored(Model $model): void
    {
        $this->rekam($model, AksiAuditLog::Pulihkan, null, null);
    }

    /**
     * Membuang kolom yang tidak boleh/tidak perlu dicatat.
     *
     * @param  array<string, mixed>  $atribut
     * @return array<string, mixed>
     */
    private function saring(array $atribut): array
    {
        return array_diff_key($atribut, array_flip(self::DIKECUALIKAN));
    }

    /**
     * @param  array<string, mixed>|null  $lama
     * @param  array<string, mixed>|null  $baru
     */
    private function rekam(Model $model, AksiAuditLog $aksi, ?array $lama, ?array $baru): void
    {
        $permintaan = request();

        AuditLog::create([
            'user_id' => Auth::id(),
            'aksi' => $aksi,
            'nama_tabel' => $model->getTable(),
            'record_id' => $model->getKey(),
            'data_lama' => $lama !== null && $lama !== [] ? $lama : null,
            'data_baru' => $baru !== null && $baru !== [] ? $baru : null,
            'ip_address' => $permintaan->ip(),
            'user_agent' => Str::limit((string) $permintaan->userAgent(), 255, ''),
        ]);
    }
}
