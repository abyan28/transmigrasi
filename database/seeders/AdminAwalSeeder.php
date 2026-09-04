<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Akun Admin pertama (Task 3.5, `rules.md` 14b poin 1).
 *
 * Tanpa pendaftaran mandiri, sistem yang baru dipasang tidak memiliki satu
 * pun akun -- tidak ada jalan masuk. Seeder ini menanam SATU akun Admin bila
 * belum ada akun berrole Admin (role terkunci) mana pun.
 *
 * - Kata sandi dari `SIM_ADMIN_PASSWORD` bila diset; selain itu dibangkitkan
 *   acak dan dicetak SATU KALI ke terminal (`rules.md` 14b poin 3).
 * - `password_harus_diganti = TRUE`: Admin pertama tetap wajib mengganti
 *   sandi + membuat username saat masuk pertama (poin 5). Dapat dimatikan
 *   lewat `SIM_ADMIN_WAJIB_GANTI=false` UNTUK PENGEMBANGAN LOKAL saja, supaya
 *   akun telusur tidak terlempar ke halaman ganti sandi tiap kali basis data
 *   dibangun ulang. Default `true` -- pemasangan di server tetap patuh poin 5
 *   tanpa perlu menyetel apa pun.
 * - Username: `SIM_ADMIN_USERNAME` bila diisi; selain itu username SEMENTARA
 *   (`petugas.xxxxxxxx`), sehingga Admin awal ikut membuat miliknya sendiri
 *   saat masuk pertama (poin 5), sama seperti akun yang dibuat lewat sistem.
 * - Pengecualian ini HANYA berlaku bagi akun seed ini. Akun yang dibuat Admin
 *   lewat sistem tetap ditandai wajib-ganti oleh `PengaturanPenggunaController`.
 * - Idempoten: dijalankan ulang tidak membuat akun kedua.
 */
class AdminAwalSeeder extends Seeder
{
    public function run(): void
    {
        $roleAdmin = Role::where('is_terkunci', true)->first();

        if ($roleAdmin === null) {
            $this->command?->warn('Role Admin belum ada -- jalankan PermissionRoleSeeder lebih dulu.');

            return;
        }

        if (User::where('role_id', $roleAdmin->id_role)->exists()) {
            $this->command?->info('Akun Admin sudah ada -- AdminAwalSeeder dilewati.');

            return;
        }

        $email = env('SIM_ADMIN_EMAIL', 'admin@malakakab.go.id');
        $sandiDiset = (string) env('SIM_ADMIN_PASSWORD', '');
        $sandi = $sandiDiset !== '' ? $sandiDiset : Str::password(16, symbols: false);

        $admin = new User;
        $admin->forceFill([
            'role_id' => $roleAdmin->id_role,
            'nama' => env('SIM_ADMIN_NAMA', 'ADMINISTRATOR SISTEM'),
            // `user.username` NOT NULL; bila `SIM_ADMIN_USERNAME` tak diisi,
            // Admin membuat miliknya sendiri saat masuk pertama.
            'username' => env('SIM_ADMIN_USERNAME') ?: User::buatUsernameSementara(),
            'email' => $email,
            'password' => $sandi,
            'is_aktif' => true,
            'password_harus_diganti' => (bool) env('SIM_ADMIN_WAJIB_GANTI', true),
        ])->save();

        $this->command?->info("Akun Admin awal dibuat: {$email}");

        if ($sandiDiset === '') {
            $this->command?->warn("Kata sandi sementara (tampil sekali): {$sandi}");
        }
    }
}
