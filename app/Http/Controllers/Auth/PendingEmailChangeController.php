<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AksiAuditLog;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\KodePemulihanSandi;
use App\Models\PendingEmailChange;
use App\Models\User;
use App\Support\ValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PendingEmailChangeController extends Controller
{
    private const INVALID_MESSAGE = 'Tautan verifikasi tidak valid atau sudah kedaluwarsa.';

    public function show(string $token): View
    {
        $pending = $this->findValid($token);

        return view('pages.auth.confirm-email-change', [
            'title' => 'Konfirmasi Email Baru',
            'valid' => $pending !== null,
            'newEmail' => $pending?->new_email,
            'mustSetPassword' => (bool) $pending?->user?->password_harus_diganti,
            'mustSetUsername' => (bool) $pending?->user?->perluBuatUsername(),
            'invalidMessage' => self::INVALID_MESSAGE,
            'token' => $token,
        ]);
    }

    public function confirm(Request $request, string $token): RedirectResponse
    {
        $preview = $this->findValid($token);

        if ($preview === null || $preview->user === null) {
            return $this->invalid($token);
        }

        $rules = [];
        $messages = [];

        if ($preview->user->password_harus_diganti) {
            $rules['password'] = ValidationRules::password();

            if ($preview->user->perluBuatUsername()) {
                $rules['username'] = [...ValidationRules::username($preview->user_id), 'not_regex:/^petugas\./'];
                $messages['username.not_regex'] = 'Pilih username permanen yang tidak diawali petugas.';
            }
        }

        $data = $request->validate($rules, ValidationRules::pesan() + $messages);
        $actorId = Auth::id();
        $tokenHash = hash('sha256', $token);

        try {
            $confirmedUserId = DB::transaction(function () use ($request, $tokenHash, $data): ?int {
                $pendingId = PendingEmailChange::query()
                    ->where('token_hash', $tokenHash)
                    ->value('id_pending_email_change');

                if ($pendingId === null) {
                    return null;
                }

                $pendingUserId = PendingEmailChange::query()
                    ->whereKey($pendingId)
                    ->value('user_id');
                $user = User::query()->lockForUpdate()->find($pendingUserId);
                $pending = PendingEmailChange::query()
                    ->whereKey($pendingId)
                    ->valid()
                    ->lockForUpdate()
                    ->first();

                if ($pending === null || $user === null || User::withTrashed()
                    ->where('email', $pending->new_email)
                    ->where('id_user', '!=', $user->id_user)
                    ->exists()) {
                    return null;
                }

                $oldEmail = $user->email;
                $attributes = [
                    'email' => $pending->new_email,
                    'remember_token' => Str::random(60),
                ];

                if ($user->password_harus_diganti) {
                    if (! isset($data['password'])) {
                        return null;
                    }

                    $attributes['password'] = $data['password'];
                    $attributes['password_harus_diganti'] = false;

                    if ($user->perluBuatUsername()) {
                        if (! isset($data['username'])) {
                            return null;
                        }

                        $attributes['username'] = $data['username'];
                    }
                }

                $user->forceFill($attributes)->save();
                $pending->forceFill(['used_at' => now()])->save();

                KodePemulihanSandi::query()
                    ->where('user_id', $user->id_user)
                    ->whereNull('dipakai_pada')
                    ->update(['dipakai_pada' => now()]);

                if (config('session.driver') === 'database') {
                    DB::connection(config('session.connection'))
                        ->table(config('session.table', 'sessions'))
                        ->where('user_id', $user->id_user)
                        ->delete();
                }

                AuditLog::create([
                    'user_id' => $user->id_user,
                    'aksi' => AksiAuditLog::Ubah,
                    'nama_tabel' => 'user',
                    'record_id' => $user->id_user,
                    'data_lama' => ['email' => $oldEmail],
                    'data_baru' => ['email' => $user->email, 'jalur' => 'Verifikasi email'],
                    'ip_address' => $request->ip(),
                    'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
                ]);

                return $user->id_user;
            });
        } catch (QueryException $e) {
            if ((string) $e->getCode() !== '23000') {
                throw $e;
            }

            $confirmedUserId = null;
        }

        if ($confirmedUserId === null) {
            return $this->invalid($token);
        }

        if ($actorId === $confirmedUserId) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('login')
            ->with('sukses', 'Email baru berhasil diverifikasi. Silakan masuk kembali menggunakan email baru Anda.');
    }

    private function findValid(string $token): ?PendingEmailChange
    {
        return PendingEmailChange::query()
            ->with('user')
            ->where('token_hash', hash('sha256', $token))
            ->valid()
            ->first();
    }

    private function invalid(string $token): RedirectResponse
    {
        return redirect()->route('email-change.show', ['token' => $token])
            ->withErrors(['token' => self::INVALID_MESSAGE]);
    }
}
