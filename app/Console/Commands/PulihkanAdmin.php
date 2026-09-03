<?php

namespace App\Console\Commands;

use App\Enums\AksiAuditLog;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Pemulihan darurat kata sandi Admin lewat terminal server (Task 3.5b,
 * `rules.md` 14b poin 17).
 *
 * Satu-satunya jalur yang bekerja tanpa sambungan surel maupun akun yang
 * masih bisa masuk. Dipakai bila SELURUH Admin kehilangan akses.
 *
 * - Menyetel ulang kata sandi satu akun Admin (role terkunci) menjadi nilai
 *   acak yang dicetak ke terminal, lalu menandai `password_harus_diganti`.
 * - Bila hanya ada satu akun Admin, argumen boleh dikosongkan. Bila lebih
 *   dari satu, argumen `identitas` (username atau email) wajib.
 * - Tercatat di audit log beraksi `Reset Kata Sandi`, `user_id` NULL (sistem),
 *   jalur `Artisan darurat` (`rules.md` 14b poin 15).
 */
class PulihkanAdmin extends Command
{
    protected $signature = 'sim:pulihkan-admin {identitas? : Username atau email akun Admin sasaran}';

    protected $description = 'Menyetel ulang kata sandi satu akun Admin lewat terminal (pemulihan darurat)';

    public function handle(): int
    {
        $identitas = $this->argument('identitas');

        $kueri = User::query()->whereHas('role', fn ($q) => $q->where('is_terkunci', true));

        if ($identitas !== null) {
            $kueri->where(fn ($q) => $q->where('username', $identitas)->orWhere('email', $identitas));
        }

        $kandidat = $kueri->get();

        if ($kandidat->isEmpty()) {
            $this->error($identitas === null
                ? 'Tidak ada akun Admin di sistem.'
                : "Tidak ada akun Admin dengan username atau email '{$identitas}'.");

            return self::FAILURE;
        }

        if ($kandidat->count() > 1) {
            $this->error('Ada lebih dari satu akun Admin. Sebutkan username atau email pada argumen:');
            $this->table(['Username', 'Email'], $kandidat->map(fn ($u) => [$u->username, $u->email]));

            return self::FAILURE;
        }

        $admin = $kandidat->first();
        $sandi = Str::password(16, symbols: false);

        $admin->forceFill([
            'password' => $sandi,
            'password_harus_diganti' => true,
        ])->save();

        AuditLog::create([
            'user_id' => null,
            'aksi' => AksiAuditLog::ResetKataSandi,
            'nama_tabel' => 'user',
            'record_id' => $admin->id_user,
            'data_baru' => ['jalur' => 'Artisan darurat'],
        ]);

        $this->info("Kata sandi Admin '{$admin->username}' ({$admin->email}) disetel ulang.");
        $this->warn("Kata sandi sementara (tampil sekali): {$sandi}");
        $this->line('Admin wajib menggantinya saat masuk berikutnya.');

        return self::SUCCESS;
    }
}
