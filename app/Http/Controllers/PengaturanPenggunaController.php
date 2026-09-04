<?php

namespace App\Http\Controllers;

use App\Enums\AksiAuditLog;
use App\Enums\CakupanData;
use App\Mail\KredensialAkunMail;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Support\DummyData;
use App\Support\Paginasi;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Manajemen pengguna oleh Admin (Task 3.5). `rules.md` 14b.
 *
 * - Tidak ada pendaftaran mandiri; seluruh akun dibuat di sini (poin 1).
 * - Kata sandi awal DIBANGKITKAN sistem, ditandai `password_harus_diganti`,
 *   ditampilkan SATU KALI (poin 3) DAN dikirim ke surel petugas sebagai
 *   salinan (poin 3a). Admin tak pernah dapat membacanya ulang; hanya hash
 *   yang tersimpan (poin 14).
 * - Username TIDAK diisi Admin (poin 5). Skema `user.username` NOT NULL,
 *   sehingga akun baru diberi username SEMENTARA (`petugas.xxxxxxxx`, format
 *   sah). Penggantiannya oleh petugas sendiri saat masuk pertama menyusul
 *   bersama form ganti-kata-sandi -- lihat session-notes "DITUNDA".
 * - Akun dinonaktifkan (`is_aktif = false`), tidak dihapus. Modul `pengguna`
 *   memang tak punya kewenangan `hapus` (`rules.md` 5.1 + `DummyData::daftarIzin`).
 * - Admin aktif terakhir tidak dapat dinonaktifkan (poin 16).
 * - Tiap penyetelan ulang / perubahan keadaan tercatat di audit log (poin 15).
 *
 * `index()` membaca Eloquent langsung (Fase 1, 2026-09-05) -- sebelumnya
 * masih membaca `DummyData` walau `simpan/perbarui/hapus` sudah menulis tabel
 * `user` sungguhan sejak Task 3.5, sehingga akun baru tak pernah muncul pada
 * daftarnya sendiri. Celah itu jatuh di antara Task 3.5 dan Tahap 4 (yang
 * ternyata tak pernah menyentuh modul ini) dan baru tertutup di sini.
 */
class PengaturanPenggunaController extends Controller
{
    public function index(Request $request): View
    {
        $cari = trim((string) $request->input('cari', ''));
        $filterRole = $request->input('role');
        $filterAktif = $request->input('aktif');
        $perHalaman = Paginasi::perHalaman($request);

        $baris = User::query()
            ->with(['role', 'satuanPermukiman'])
            ->when($cari !== '', fn ($q) => $q->where(fn ($sub) => $sub
                ->where('nama', 'like', "%{$cari}%")
                ->orWhere('username', 'like', "%{$cari}%")))
            ->when($filterRole, fn ($q) => $q->whereHas('role', fn ($r) => $r->where('nama', $filterRole)))
            ->when($filterAktif !== null && $filterAktif !== '', fn ($q) => $q->where('is_aktif', (bool) $filterAktif))
            ->orderBy('nama')
            ->paginate($perHalaman)
            ->withQueryString();

        // $inisial hanya dipakai VIEW untuk baris yang TAMPIL, jadi cukup
        // dihitung dari model mentah HALAMAN INI.
        $inisial = [];
        foreach ($baris->getCollection() as $u) {
            $inisial[$u->id_user] = DummyData::inisial($u->nama);
        }

        $baris->through(fn (User $u) => $this->baris($u));

        return view('pages.pengguna.index', [
            'title' => 'Manajemen Pengguna',
            'baris' => $baris,
            'cari' => $cari,
            'filterRole' => $filterRole,
            'filterAktif' => $filterAktif,
            'adaFilter' => $cari !== '' || $filterRole || ($filterAktif !== null && $filterAktif !== ''),
            // Kartu ringkasan kawasan-penuh, bukan hasil saringan/halaman ini.
            'totalAkun' => User::query()->count(),
            'aktif' => User::query()->where('is_aktif', true)->count(),
            'perluGanti' => User::query()->where('password_harus_diganti', true)->count(),
            // Hanya role yang benar-benar punya pemegang, sama seperti dahulu
            // (turunan `$semua`, bukan seluruh isi tabel role).
            'daftarRole' => Role::query()->whereHas('users')->orderBy('nama')->pluck('nama')->all(),
            'jumlahAdminAktif' => User::query()
                ->where('is_aktif', true)
                ->whereHas('role', fn ($q) => $q->where('is_terkunci', true))
                ->count(),
            'inisial' => $inisial,
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);
        $role = Role::findOrFail($data['role_id']);
        $spIds = $this->validasiPenugasanSp($request, $role);

        $sandiSementara = $this->sandiSementara();

        $pengguna = new User;
        $pengguna->forceFill([
            'role_id' => $role->id_role,
            'nama' => $data['nama'],
            'username' => User::buatUsernameSementara(),
            'email' => $data['email'],
            'password' => $sandiSementara,
            'telepon' => $data['telepon'] ?? null,
            'jabatan' => $data['jabatan'] ?? null,
            'is_aktif' => true,
            'password_harus_diganti' => true,
        ])->save();

        if ($spIds !== []) {
            $pengguna->satuanPermukiman()->sync($spIds);
        }

        $this->catat($request, $pengguna, AksiAuditLog::Tambah, [
            'nama' => $pengguna->nama,
            'email' => $pengguna->email,
            'role' => $role->nama,
        ]);

        $this->kirimKredensial($pengguna, $sandiSementara, akunBaru: true);

        return redirect()->route('pengguna.index')
            ->with('sukses', 'Akun petugas tersimpan. Serahkan kata sandi sementara secara langsung.')
            ->with('kredensial_baru', [
                'nama' => $pengguna->nama,
                'email' => $pengguna->email,
                'password' => $sandiSementara,
            ]);
    }

    public function perbarui(Request $request, int $id): RedirectResponse
    {
        $pengguna = User::findOrFail($id);
        $data = $this->validasi($request, $pengguna);
        $role = Role::findOrFail($data['role_id']);
        $spIds = $this->validasiPenugasanSp($request, $role);

        $lama = $pengguna->only(['nama', 'email', 'role_id', 'telepon', 'jabatan']);

        $pengguna->forceFill([
            'role_id' => $role->id_role,
            'nama' => $data['nama'],
            'email' => $data['email'],
            'telepon' => $data['telepon'] ?? null,
            'jabatan' => $data['jabatan'] ?? null,
        ])->save();

        // Penugasan SP hanya bermakna bagi role `Per SP`; role lain -> lepas
        // seluruhnya agar tidak ada penugasan menggantung bila role diturunkan.
        $pengguna->satuanPermukiman()->sync(
            $role->cakupan_data === CakupanData::PerSp ? $spIds : []
        );

        $this->catat($request, $pengguna, AksiAuditLog::Ubah, [
            'sebelum' => $lama,
            'sesudah' => $pengguna->only(['nama', 'email', 'role_id', 'telepon', 'jabatan']),
        ]);

        return redirect()->route('pengguna.index')->with('sukses', 'Perubahan data akun tersimpan.');
    }

    public function setelSandi(Request $request, int $id): RedirectResponse
    {
        $pengguna = User::findOrFail($id);
        $sandiSementara = $this->sandiSementara();

        $pengguna->forceFill([
            'password' => $sandiSementara,
            'password_harus_diganti' => true,
        ])->save();

        // `rules.md` 14b poin 15: catat pelaku, sasaran, waktu, dan JALUR.
        $this->catat($request, $pengguna, AksiAuditLog::ResetKataSandi, ['jalur' => 'Admin']);

        $this->kirimKredensial($pengguna, $sandiSementara, akunBaru: false);

        return redirect()->route('pengguna.index')
            ->with('sukses', 'Kata sandi sementara dibuat. Serahkan langsung kepada petugas yang bersangkutan.')
            ->with('kredensial_baru', [
                'nama' => $pengguna->nama,
                'email' => $pengguna->email,
                'password' => $sandiSementara,
            ]);
    }

    public function nonaktifkan(Request $request, int $id): RedirectResponse
    {
        $pengguna = User::findOrFail($id);

        // `rules.md` 14b poin 16 -- diperiksa DI SERVER, bukan hanya lewat
        // penyembunyian tombol.
        abort_if(
            $this->adminAktifTerakhir($pengguna),
            422,
            'Tidak dapat menonaktifkan Admin aktif terakhir. Sistem harus selalu punya satu jalur administrasi.',
        );

        $pengguna->forceFill(['is_aktif' => false])->save();
        $this->catat($request, $pengguna, AksiAuditLog::NonaktifkanAkun, ['nama' => $pengguna->nama]);

        return redirect()->route('pengguna.index')
            ->with('sukses', 'Akun dinonaktifkan. Seluruh riwayat tindakannya tetap tersimpan.');
    }

    public function aktifkan(Request $request, int $id): RedirectResponse
    {
        $pengguna = User::findOrFail($id);

        $pengguna->forceFill(['is_aktif' => true])->save();
        $this->catat($request, $pengguna, AksiAuditLog::AktifkanAkun, ['nama' => $pengguna->nama]);

        return redirect()->route('pengguna.index')
            ->with('sukses', 'Akun diaktifkan kembali. Petugas dapat masuk memakai kredensial yang sama.');
    }

    /**
     * Larik ber-kunci PERSIS bentuk lama `DummyData::pengguna()` supaya view
     * tak perlu disentuh.
     *
     * @return array<string, mixed>
     */
    private function baris(User $u): array
    {
        return [
            'id_user' => $u->id_user,
            'nama' => $u->nama,
            'username' => $u->username,
            'email' => $u->email,
            'role' => $u->role?->nama,
            'role_id' => $u->role_id,
            'jabatan' => $u->jabatan,
            'telepon' => $u->telepon,
            'is_aktif' => $u->is_aktif,
            'password_harus_diganti' => $u->password_harus_diganti,
            'last_login_at' => $u->last_login_at?->format('Y-m-d H:i:s'),
            'satuan_permukiman' => $u->satuanPermukiman->pluck('nama')->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?User $pengguna = null): array
    {
        return $request->validate([
            'nama' => ValidationRules::nama(),
            'email' => ValidationRules::email(abaikanId: $pengguna?->id_user),
            'role_id' => ['required', 'integer', 'exists:role,id_role'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'telepon' => ValidationRules::telepon(),
        ], ValidationRules::pesan() + [
            'role_id.required' => 'Role wajib dipilih.',
            'role_id.exists' => 'Role yang dipilih tidak ada.',
        ], ValidationRules::label());
    }

    /**
     * Penugasan SP wajib minimal satu bila role bercakupan `Per SP`
     * (`rules.md` 14b poin 2); diabaikan untuk role lain.
     *
     * @return array<int, int>
     */
    private function validasiPenugasanSp(Request $request, Role $role): array
    {
        if ($role->cakupan_data !== CakupanData::PerSp) {
            return [];
        }

        $data = $request->validate([
            'satuan_permukiman' => ['required', 'array', 'min:1'],
            'satuan_permukiman.*' => ['integer', 'exists:satuan_permukiman,id_satuan_permukiman'],
        ], [
            'satuan_permukiman.required' => 'Role Per SP wajib diberi minimal satu penugasan SP.',
            'satuan_permukiman.min' => 'Role Per SP wajib diberi minimal satu penugasan SP.',
        ]);

        return array_map('intval', $data['satuan_permukiman']);
    }

    /**
     * Apakah `$pengguna` adalah satu-satunya akun Admin yang masih aktif.
     * Role Admin dikenali dari `is_terkunci` (hanya role Admin yang terkunci,
     * `rules.md` 5.0a) sehingga penggantian nama role tidak melubangi lindungan.
     */
    private function adminAktifTerakhir(User $pengguna): bool
    {
        if (! $pengguna->is_aktif || ! ($pengguna->role?->is_terkunci ?? false)) {
            return false;
        }

        return User::query()
            ->where('is_aktif', true)
            ->whereHas('role', fn ($q) => $q->where('is_terkunci', true))
            ->count() === 1;
    }

    private function sandiSementara(): string
    {
        // Tanpa simbol -- kata sandi ini diketik ulang petugas di lokus, dan
        // dibacakan lewat telepon bila perlu. Tetap lolos aturan (huruf + angka).
        return Str::password(14, symbols: false);
    }

    /**
     * Kirim kata sandi sementara ke surel petugas sebagai SALINAN penyerahan
     * langsung (`rules.md` 14b poin 3a). Gangguan SMTP tidak boleh menggagalkan
     * pembuatan akun -- Admin masih memegang nilai yang tampil di layar.
     */
    private function kirimKredensial(User $pengguna, string $sandiSementara, bool $akunBaru): void
    {
        try {
            Mail::to($pengguna->email)->send(
                new KredensialAkunMail($pengguna->nama, $pengguna->email, $sandiSementara, $akunBaru)
            );
        } catch (\Throwable $e) {
            Log::error('Gagal mengirim kredensial akun ke surel: '.$e->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function catat(Request $request, User $pengguna, AksiAuditLog $aksi, array $data): void
    {
        AuditLog::create([
            'user_id' => $request->user()?->id_user,
            'aksi' => $aksi,
            'nama_tabel' => 'user',
            'record_id' => $pengguna->id_user,
            'data_baru' => $data,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
        ]);
    }
}
