<?php

namespace App\Support;

use App\Enums\AsalWakilPoktan;
use App\Models\AnggotaPoktan;
use App\Models\Lahan;
use App\Models\Poktan;

/**
 * Penyajian data Kelompok Tani sebagai larik siap pakai (Task 10.5).
 *
 * Sebelumnya logika pemetaan ini hidup sebagai metode privat di
 * `PoktanController` dengan janji docblock "larik ber-kunci PERSIS satu baris
 * `DummyData::poktan()`". Ketika `LaporanData` juga perlu membaca poktan dari
 * Eloquent (bukan lagi `DummyData`), logika yang sama dipindahkan ke sini agar
 * halaman daftar, rincian, dan laporan membaca satu sumber.
 *
 * KETUA/WAKIL punya tiga asal-usul (`rules.md` 7a.2a): Kepala Keluarga &
 * Anggota Keluarga membaca identitas lewat relasi; Bukan Transmigran memakai
 * nilai yang diketik. `jumlah_anggota` dan luas lahan kelompok DITURUNKAN
 * (`App\Support\RekapPoktan` / `RekapLahan`), tidak disimpan.
 */
class PenyajianPoktan
{
    /**
     * Semua poktan, satu larik per baris dengan kunci persis `DummyData::poktan()`.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function daftar(): array
    {
        return Poktan::query()
            ->withoutGlobalScopes()
            ->with(['satuanPermukiman', 'ketuaTransmigran', 'ketuaAnggotaKeluarga', 'berkas', 'anggota'])
            ->orderBy('id_poktan')
            ->get()
            ->map(fn (Poktan $p): array => self::baris($p))
            ->all();
    }

    /**
     * Semua anggota poktan, kunci persis `DummyData::anggotaPoktan()` (setelah map).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function daftarAnggota(?int $poktanId = null): array
    {
        return AnggotaPoktan::query()
            ->withoutGlobalScopes()
            ->with(['transmigran', 'anggotaKeluarga', 'poktan'])
            ->when($poktanId !== null, fn ($q) => $q->where('poktan_id', $poktanId))
            ->orderBy('id_anggota_poktan')
            ->get()
            ->map(fn (AnggotaPoktan $a): array => self::barisAnggota($a))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function baris(Poktan $p): array
    {
        $ketua = self::identitasKetua($p);
        $kekuatan = RekapPoktan::kekuatan($p);

        return [
            'id_poktan' => $p->id_poktan,
            'nama' => $p->nama,
            'satuan_permukiman' => $p->satuanPermukiman?->nama,
            'satuan_permukiman_id' => $p->satuan_permukiman_id,
            'asal_ketua' => $p->asal_ketua->value,
            'ketua_transmigran_id' => $p->ketua_transmigran_id,
            'ketua_anggota_keluarga_id' => $p->ketua_anggota_keluarga_id,
            'nama_ketua' => $ketua['nama'],
            'nik_ketua' => $ketua['nik'] === '-' ? null : $ketua['nik'],
            'hubungan_ketua' => $ketua['hubungan'],
            'telepon_ketua' => $p->telepon_ketua,
            'email_ketua' => $p->email_ketua,
            'alamat_ketua' => $p->alamat_ketua,
            'tahun_berdiri' => $p->tahun_berdiri === null ? null : (int) $p->tahun_berdiri,
            'jumlah_anggota' => $kekuatan['jumlah_anggota'],
            'luas_kering_ketua' => $p->luas_kering_ketua === null ? null : (float) $p->luas_kering_ketua,
            'luas_basah_ketua' => $p->luas_basah_ketua === null ? null : (float) $p->luas_basah_ketua,
            'lintang' => $p->lintang === null ? null : (float) $p->lintang,
            'bujur' => $p->bujur === null ? null : (float) $p->bujur,
            'keterangan' => $p->keterangan,
            'dokumen_pendukung' => $p->berkas?->nama_file,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function barisAnggota(AnggotaPoktan $a): array
    {
        $identitas = self::identitasWakilAnggota($a);
        $lahan = RekapLahan::keluarga(
            $a->transmigran_id === null ? null : Lahan::withoutGlobalScopes()->where('transmigran_id', $a->transmigran_id)->first(),
        );

        return [
            'id_anggota_poktan' => $a->id_anggota_poktan,
            'poktan_id' => $a->poktan_id,
            'poktan' => $a->poktan?->nama,
            'transmigran_id' => $a->transmigran_id,
            'asal_wakil' => $a->asal_wakil->value,
            'anggota_keluarga_id' => $a->anggota_keluarga_id,
            'telepon_wakil' => $a->telepon_wakil,
            'jabatan' => $a->jabatan,
            'tanggal_masuk' => $a->tanggal_masuk?->toDateString(),
            'tanggal_keluar' => $a->tanggal_keluar?->toDateString(),
            'status' => $a->status->value,
            'alasan_keluar' => $a->alasan_keluar,
            'keterangan' => $a->keterangan,
            'nama' => $identitas['nama'],
            'nik' => $identitas['nik'],
            'telepon' => $identitas['telepon'],
            'hubungan_wakil' => $identitas['hubungan'],
            'luas_kering' => $lahan['kering'],
            'luas_basah' => $lahan['basah'],
            'lintang' => $lahan['lintang'],
            'bujur' => $lahan['bujur'],
        ];
    }

    /**
     * Identitas ketua dari jalur mana pun (`rules.md` 7a.2a).
     *
     * @return array{nama: string, nik: string, telepon: string|null, hubungan: string|null, asal: AsalWakilPoktan}
     */
    public static function identitasKetua(Poktan $p): array
    {
        $asal = $p->asal_ketua;

        if ($asal === AsalWakilPoktan::KepalaKeluarga && $p->ketuaTransmigran !== null) {
            return [
                'nama' => $p->ketuaTransmigran->nama_kepala_keluarga,
                'nik' => $p->ketuaTransmigran->nik,
                'telepon' => $p->telepon_ketua ?: $p->ketuaTransmigran->telepon,
                'hubungan' => null,
                'asal' => $asal,
            ];
        }

        if ($asal === AsalWakilPoktan::AnggotaKeluarga && $p->ketuaAnggotaKeluarga !== null) {
            $ak = $p->ketuaAnggotaKeluarga;

            return [
                'nama' => $ak->nama_lengkap,
                'nik' => $ak->nik ?? '-',
                'telepon' => $ak->telepon ?: $p->ketuaTransmigran?->telepon,
                'hubungan' => $ak->hubungan->value,
                'asal' => $asal,
            ];
        }

        return [
            'nama' => $p->nama_ketua ?? '-',
            'nik' => $p->nik_ketua ?? '-',
            'telepon' => $p->telepon_ketua,
            'hubungan' => null,
            'asal' => $asal,
        ];
    }

    /**
     * @return array{nama: string, nik: string, telepon: string|null, hubungan: string|null}
     */
    public static function identitasWakilAnggota(AnggotaPoktan $a): array
    {
        if ($a->asal_wakil === AsalWakilPoktan::AnggotaKeluarga && $a->anggotaKeluarga !== null) {
            $ak = $a->anggotaKeluarga;

            return [
                'nama' => $ak->nama_lengkap,
                'nik' => $ak->nik ?? '-',
                'telepon' => $a->telepon_wakil ?: ($ak->telepon ?: $a->transmigran?->telepon),
                'hubungan' => $ak->hubungan->value,
            ];
        }

        return [
            'nama' => $a->transmigran?->nama_kepala_keluarga ?? '-',
            'nik' => $a->transmigran?->nik ?? '-',
            'telepon' => $a->telepon_wakil ?: $a->transmigran?->telepon,
            'hubungan' => null,
        ];
    }
}
