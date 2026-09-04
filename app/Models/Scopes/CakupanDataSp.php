<?php

namespace App\Models\Scopes;

use App\Enums\BidangPengaduan;
use App\Enums\CakupanData;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Global scope cakupan data (`rules.md` 5.0b + rancangan mengikat 5.0b-1).
 *
 * TITIK PENEGAKAN TUNGGAL: penyaring melekat pada MODEL pemilik SP, bukan
 * diulang di tiap pemanggil. Model turunan memakai trait `DisaringLewatInduk`
 * yang mendelegasikan ke induknya (yang scope-nya sudah aktif) -- tidak
 * menyalin logika SP.
 *
 * TIDAK DISARING (poin 9a/9b): `alsintan`/`saprotan` induk (deskripsi benda),
 * seluruh data referensi (wilayah, kawasan, satuan, komoditas, daftar_pilihan,
 * parameter_penilaian_sp, status_kondisi_sp, role, permission), dan
 * `SatuanPermukiman` sendiri.
 *
 * Tampilan masih memakai `DummyData` sampai Tahap 4, sehingga scope ini belum
 * berefek di layar -- Task 3.4 memasang mesinnya + uji Eloquent.
 */
class CakupanDataSp implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $pengguna = self::penggunaWajibDisaring();

        if ($pengguna === null) {
            return;
        }

        $cakupan = $pengguna->role->cakupan_data;
        $tabel = $model->getTable();

        if ($cakupan === CakupanData::PerSp) {
            $spIds = self::spDitugaskan($pengguna);

            // Daftar kosong -> NOL baris, BUKAN tanpa syarat (poin 10). Akun
            // `Per SP` tanpa penugasan tidak melihat data apa pun.
            $spIds === []
                ? $builder->whereRaw('1 = 0')
                : $builder->whereIn($tabel.'.satuan_permukiman_id', $spIds);

            return;
        }

        // `Per Bidang` (Dinas Pertanian) hanya berlaku pada `pengaduan`
        // (poin 14, `rules.md` 5.0b poin 6a). Model pemilik SP lain
        // berjangkauan penuh bagi role `Per Bidang`.
        if ($cakupan === CakupanData::PerBidang && $tabel === 'pengaduan') {
            $bidang = self::bidangDinas($pengguna);

            if ($bidang !== null) {
                $builder->where($tabel.'.bidang', $bidang->value);
            }
        }
    }

    /**
     * Pengguna aktif yang datanya wajib disaring, atau null bila konteks tidak
     * menyaring: non-HTTP (artisan/seeder/job/uji tanpa `actingAs`), tanpa
     * role, atau role bercakupan `Semua`. `rules.md` 5.0b-1 poin 15.
     */
    public static function penggunaWajibDisaring(): ?User
    {
        /** @var User|null $pengguna */
        $pengguna = Auth::user();

        if ($pengguna === null || $pengguna->role === null) {
            return null;
        }

        return match ($pengguna->role->cakupan_data) {
            CakupanData::PerSp, CakupanData::PerBidang => $pengguna,
            default => null,
        };
    }

    /**
     * Id SP yang ditugaskan ke pengguna lewat `user_satuan_permukiman`.
     *
     * @return array<int, int>
     */
    public static function spDitugaskan(User $pengguna): array
    {
        return $pengguna->satuanPermukiman->pluck('id_satuan_permukiman')->all();
    }

    /**
     * Bidang pengaduan yang dilihat sebuah role `Per Bidang`. Saat ini hanya
     * Dinas Pertanian (`rules.md` 5.0b poin 6a: asimetri disengaja). Bila
     * kelak ada role `Per Bidang` lain, peta ini butuh tautan role->bidang.
     */
    public static function bidangDinas(User $pengguna): ?BidangPengaduan
    {
        if ($pengguna->role?->cakupan_data !== CakupanData::PerBidang) {
            return null;
        }

        return BidangPengaduan::Pertanian;
    }
}
