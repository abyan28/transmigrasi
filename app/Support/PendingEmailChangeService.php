<?php

namespace App\Support;

use App\Mail\AccountChangeNoticeMail;
use App\Mail\PendingEmailChangeMail;
use App\Models\PendingEmailChange;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PendingEmailChangeService
{
    public const EXPIRES_IN_MINUTES = 60;

    public function request(User $user, string $newEmail): PendingEmailChange
    {
        $newEmail = Str::lower(trim($newEmail));
        $token = bin2hex(random_bytes(32));

        [$pending, $currentEmail, $name] = DB::transaction(function () use ($user, $newEmail, $token): array {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id_user);

            PendingEmailChange::query()
                ->where('user_id', $lockedUser->id_user)
                ->whereNull('used_at')
                ->whereNull('cancelled_at')
                ->update(['cancelled_at' => now()]);

            $pending = PendingEmailChange::create([
                'user_id' => $lockedUser->id_user,
                'new_email' => $newEmail,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addMinutes(self::EXPIRES_IN_MINUTES),
            ]);

            Mail::to($newEmail)->queue(new PendingEmailChangeMail(
                $lockedUser->nama,
                $newEmail,
                $token,
                self::EXPIRES_IN_MINUTES,
            ));

            if ($lockedUser->password_harus_diganti) {
                $lockedUser->forceFill([
                    'password' => bin2hex(random_bytes(32)),
                    'remember_token' => Str::random(60),
                ])->save();

                if (config('session.driver') === 'database') {
                    DB::connection(config('session.connection'))
                        ->table(config('session.table', 'sessions'))
                        ->where('user_id', $lockedUser->id_user)
                        ->delete();
                }
            }

            return [$pending, $lockedUser->email, $lockedUser->nama];
        });

        $this->sendNotice(
            $currentEmail,
            $name,
            "Permintaan perubahan email akun ke {$newEmail} telah dibuat. Email login saat ini tetap {$currentEmail} sampai pemilik email baru mengonfirmasinya.",
        );

        return $pending;
    }

    public function sendAccountChangeNotice(User $user, string $message): void
    {
        $this->sendNotice($user->email, $user->nama, $message);
    }

    private function sendNotice(string $email, string $name, string $message): void
    {
        try {
            Mail::to($email)->queue(new AccountChangeNoticeMail($name, $message));
        } catch (\Throwable $e) {
            Log::error('Gagal mengantre pemberitahuan perubahan akun: '.$e->getMessage());
        }
    }
}
