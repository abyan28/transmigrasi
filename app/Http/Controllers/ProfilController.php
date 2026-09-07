<?php

namespace App\Http\Controllers;

use App\Enums\AksiAuditLog;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\PendingEmailChangeService;
use App\Support\PetaPenggunaTampilan;
use App\Support\SesiPengguna;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Profil pengguna sendiri (Task 3.13).
 *
 * Petugas hanya boleh menyunting DATA KONTAK dirinya (email, telepon). Nama,
 * username, role, dan penugasan SP ditetapkan Admin lewat Manajemen Pengguna
 * (`rules.md` 14b poin 1-2) -- di sini baca-saja. Username dibuat petugas saat
 * masuk pertama lewat alur ganti-kata-sandi wajib, bukan di sini (poin 5).
 *
 * Ubah kata sandi atas kehendak sendiri: memeriksa kata sandi lama lebih dulu,
 * lalu mencatat audit `Reset Kata Sandi` atas nama pemilik akun, jalur
 * `Mandiri` (poin 15). TIDAK menyetel `password_harus_diganti` (poin 13 --
 * flag itu hanya untuk kata sandi sementara buatan Admin).
 */
class ProfilController extends Controller
{
    public function index(Request $request): View
    {
        return view('pages.profil.index', [
            'title' => 'Profil Saya',
            'pengguna' => PetaPenggunaTampilan::untuk($request->user()),
            'inisialPengguna' => Str::initials(Str::words($request->user()?->nama ?? 'Pengguna', 2, ''), true),
        ]);
    }

    public function simpan(Request $request, PendingEmailChangeService $emailChanges): RedirectResponse
    {
        /** @var User $pengguna */
        $pengguna = $request->user();
        $emailBerubah = strcasecmp((string) $request->input('email'), $pengguna->email) !== 0;
        $aturan = [
            'email' => ValidationRules::email(abaikanId: $pengguna->id_user),
            'telepon' => ValidationRules::telepon(),
        ];

        if ($emailBerubah) {
            $aturan['current_password'] = ['required', 'current_password'];
        }

        $data = $request->validate($aturan, ValidationRules::pesan() + [
            'current_password.required' => 'Kata sandi saat ini wajib diisi untuk mengganti email.',
            'current_password.current_password' => 'Kata sandi saat ini tidak cocok.',
        ], ValidationRules::label());

        $lama = $pengguna->only(['email', 'telepon']);
        $pengguna->forceFill(['telepon' => $data['telepon'] ?? null])->save();
        $sesudah = $pengguna->only(['email', 'telepon']);

        $this->catat($request, AksiAuditLog::Ubah, [
            'sebelum' => $lama,
            'sesudah' => $sesudah,
            'email_baru_menunggu_verifikasi' => $emailBerubah ? $data['email'] : null,
        ]);

        if ($emailBerubah) {
            $emailChanges->request($pengguna, $data['email']);
        } elseif ($lama !== $sesudah) {
            $emailChanges->sendAccountChangeNotice($pengguna, 'Nomor telepon akun Anda telah diperbarui.');
        }

        return back()->with(
            'sukses',
            $emailBerubah
                ? 'Nomor telepon tersimpan. Email login tetap memakai alamat lama sampai email baru diverifikasi.'
                : 'Data kontak Anda tersimpan.',
        );
    }

    public function tampilKataSandi(): View
    {
        return view('pages.profil.kata-sandi', ['title' => 'Ubah Kata Sandi']);
    }

    public function simpanKataSandi(Request $request): RedirectResponse
    {
        /** @var User $pengguna */
        $pengguna = $request->user();

        $request->validate([
            'password_lama' => ['required', 'current_password'],
            'password' => ValidationRules::password(),
        ], ValidationRules::pesan() + [
            'password_lama.required' => 'Kata sandi saat ini wajib diisi.',
            'password_lama.current_password' => 'Kata sandi saat ini tidak cocok.',
        ], ValidationRules::label());

        // TANPA `password_harus_diganti`: petugas memilih sendiri sandi finalnya.
        $pengguna->forceFill(['password' => $request->input('password')])->save();
        SesiPengguna::cabut($pengguna);

        $this->catat($request, AksiAuditLog::ResetKataSandi, ['jalur' => 'Mandiri']);

        return redirect()->route('profil')->with('sukses', 'Kata sandi berhasil diperbarui.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function catat(Request $request, AksiAuditLog $aksi, array $data): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id_user,
            'aksi' => $aksi,
            'nama_tabel' => 'user',
            'record_id' => $request->user()->id_user,
            'data_baru' => $data,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
        ]);
    }
}
