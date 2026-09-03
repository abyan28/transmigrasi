<?php

namespace App\Http\Controllers;

use App\Enums\AksiAuditLog;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Halaman Audit Log (Task 3.12). HANYA-BACA.
 *
 * Task 3.6 menanam pencatatan otomatis (`AuditLogObserver`, 31 model data) +
 * pencatatan manual kejadian akun (login, reset sandi, nonaktif, ubah izin
 * role). Halaman ini menampilkannya: daftar, filter (pelaku, aksi, rentang
 * tahun, kata kunci), paginasi, dan `data_lama`/`data_baru` sebagai selisih
 * terbaca.
 *
 * Catatan audit tidak pernah dapat disunting maupun dihapus lewat antarmuka
 * mana pun -- tidak ada rute tulis, dan itulah yang membuatnya bernilai
 * sebagai jejak (`data-dictionary.md` §2.2, `rules.md` §14 poin 5).
 */
class AuditLogController extends Controller
{
    private const PER_HALAMAN = 25;

    public function index(Request $request): View
    {
        $cari = trim((string) $request->query('cari', ''));
        $filterAksi = (string) $request->query('aksi', '');
        $filterPengguna = (string) $request->query('pengguna', '');

        [$tahunDari, $tahunSampai] = $this->rentangTahun($request);

        $baris = AuditLog::query()
            ->with('pelaku')
            ->when($cari !== '', fn ($q) => $q->where(
                fn ($sub) => $sub->where('nama_tabel', 'like', "%{$cari}%")
                    ->orWhere('aksi', 'like', "%{$cari}%")
            ))
            ->when($filterAksi !== '', fn ($q) => $q->where('aksi', $filterAksi))
            ->when($filterPengguna !== '', fn ($q) => $filterPengguna === 'Sistem'
                ? $q->whereNull('user_id')
                : $q->whereHas('pelaku', fn ($sub) => $sub->where('nama', $filterPengguna)))
            ->when($tahunDari !== null, fn ($q) => $q->whereYear('created_at', '>=', $tahunDari))
            ->when($tahunSampai !== null, fn ($q) => $q->whereYear('created_at', '<=', $tahunSampai))
            ->latest('created_at')
            ->latest('id_audit_log')
            ->paginate(self::PER_HALAMAN)
            ->withQueryString();

        $baris->through(fn (AuditLog $a) => $this->petakan($a));

        return view('pages.pengguna.audit-log', [
            'title' => 'Audit Log',
            'baris' => $baris,
            'cari' => $cari,
            'filterAksi' => $filterAksi,
            'filterPengguna' => $filterPengguna,
            'filterTahunDari' => $tahunDari,
            'filterTahunSampai' => $tahunSampai,
            'adaFilter' => $cari !== '' || $filterAksi !== '' || $filterPengguna !== ''
                || $tahunDari !== null || $tahunSampai !== null,
            'daftarAksi' => $this->daftarAksi(),
            'daftarPengguna' => $this->daftarPengguna(),
            'daftarTahun' => $this->daftarTahun(),
        ]);
    }

    /**
     * Rentang tahun dari query string, ditukar bila terbalik
     * (`x-sim.filter-rentang-tahun` tidak menukar sendiri).
     *
     * @return array{0: int|null, 1: int|null}
     */
    private function rentangTahun(Request $request): array
    {
        $dari = $request->filled('tahun_dari') ? (int) $request->query('tahun_dari') : null;
        $sampai = $request->filled('tahun_sampai') ? (int) $request->query('tahun_sampai') : null;

        if ($dari !== null && $sampai !== null && $dari > $sampai) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        return [$dari, $sampai];
    }

    /**
     * Memetakan satu baris audit ke bentuk yang dipakai view.
     *
     * @return array<string, mixed>
     */
    private function petakan(AuditLog $a): array
    {
        $perubahan = $this->perubahan($a);

        return [
            'waktu' => $a->created_at,
            'pengguna' => $a->pelaku?->nama ?? 'Sistem',
            'aksi' => $a->aksi->value,
            'nama_tabel' => $a->nama_tabel,
            'record_id' => $a->record_id,
            'ip_address' => $a->ip_address,
            'ringkasan' => $this->ringkasan($a, $perubahan),
            'perubahan' => $perubahan,
        ];
    }

    /**
     * Selisih terbaca dari `data_lama`/`data_baru`. Untuk `Tambah` hanya nilai
     * baru; untuk `Hapus` hanya nilai lama; untuk `Ubah` keduanya (kunci sama).
     *
     * @return array<int, array{kolom: string, lama: string, baru: string}>
     */
    private function perubahan(AuditLog $a): array
    {
        $lama = $a->data_lama ?? [];
        $baru = $a->data_baru ?? [];

        // Pertahanan berlapis: observer sudah mengecualikan rahasia
        // (`AuditLogObserver::DIKECUALIKAN`), tetapi halaman ini tidak boleh
        // pernah menampilkannya walau baris audit terlanjur memuatnya.
        $rahasia = ['password', 'password_lama', 'password_baru', 'remember_token'];
        $lama = array_diff_key($lama, array_flip($rahasia));
        $baru = array_diff_key($baru, array_flip($rahasia));

        $kolom = array_keys($baru + $lama);

        $hasil = [];

        foreach ($kolom as $k) {
            $hasil[] = [
                'kolom' => $k,
                'lama' => array_key_exists($k, $lama) ? $this->keTeks($lama[$k]) : '-',
                'baru' => array_key_exists($k, $baru) ? $this->keTeks($baru[$k]) : '-',
            ];
        }

        return $hasil;
    }

    private function keTeks(mixed $nilai): string
    {
        if ($nilai === null || $nilai === '') {
            return '-';
        }

        if (is_bool($nilai)) {
            return $nilai ? 'ya' : 'tidak';
        }

        if (is_array($nilai)) {
            return (string) json_encode($nilai, JSON_UNESCAPED_UNICODE);
        }

        return Str::limit((string) $nilai, 120);
    }

    /**
     * @param  array<int, array{kolom: string, lama: string, baru: string}>  $perubahan
     */
    private function ringkasan(AuditLog $a, array $perubahan): string
    {
        return match ($a->aksi) {
            AksiAuditLog::Tambah => 'Menambah baris baru.',
            AksiAuditLog::Hapus => 'Menghapus baris.',
            AksiAuditLog::Pulihkan => 'Memulihkan baris yang terhapus.',
            AksiAuditLog::Ubah => $perubahan === []
                ? 'Menyunting baris.'
                : 'Mengubah '.count($perubahan).' kolom: '
                    .implode(', ', array_column($perubahan, 'kolom')).'.',
            default => $a->aksi->value.'.',
        };
    }

    /**
     * Jenis aksi yang benar-benar pernah tercatat -- bukan seluruh 10 nilai
     * enum. Opsi filter untuk aksi yang tak pernah terjadi hanya kontrol mati.
     *
     * @return array<int, string>
     */
    private function daftarAksi(): array
    {
        return AuditLog::query()->distinct()->orderBy('aksi')->pluck('aksi')
            ->map(fn ($a) => $a instanceof AksiAuditLog ? $a->value : (string) $a)
            ->all();
    }

    /**
     * Nama pelaku yang pernah muncul, ditambah "Sistem" bila ada baris tanpa
     * pelaku (proses artisan/seeder/observer non-HTTP).
     *
     * @return array<int, string>
     */
    private function daftarPengguna(): array
    {
        $idPelaku = AuditLog::query()->whereNotNull('user_id')->distinct()->pluck('user_id');

        $nama = User::query()->whereIn('id_user', $idPelaku)->orderBy('nama')->pluck('nama')->all();

        if (AuditLog::query()->whereNull('user_id')->exists()) {
            array_unshift($nama, 'Sistem');
        }

        return $nama;
    }

    /**
     * Rentang tahun contiguous dari baris terlama sampai terbaru. Portabel
     * (tanpa `YEAR()` khusus MySQL) sebab dihitung dari dua baris ekstrem.
     *
     * @return array<int, int>
     */
    private function daftarTahun(): array
    {
        $terbaru = AuditLog::query()->max('created_at');

        if ($terbaru === null) {
            return [];
        }

        $maks = (int) Str::substr((string) $terbaru, 0, 4);
        $min = (int) Str::substr((string) AuditLog::query()->min('created_at'), 0, 4);

        return range($maks, $min);
    }
}
