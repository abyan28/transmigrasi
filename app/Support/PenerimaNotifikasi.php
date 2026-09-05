<?php

namespace App\Support;

use App\Enums\CakupanData;
use App\Models\Scopes\CakupanDataSp;
use App\Models\User;
use Illuminate\Support\Collection;

class PenerimaNotifikasi
{
    public static function untuk(string $izin, ?int $spId, ?string $bidang = null): Collection
    {
        return User::query()
            ->where('is_aktif', true)
            ->whereHas('role', fn ($q) => $q->where('is_aktif', true))
            ->with(['role.permissions', 'satuanPermukiman'])
            ->get()
            ->filter(fn (User $user): bool => $user->punyaIzin($izin))
            ->filter(fn (User $user): bool => match ($user->role->cakupan_data) {
                CakupanData::Semua => true,
                CakupanData::PerSp => $spId !== null
                    && in_array($spId, CakupanDataSp::spDitugaskan($user), true),
                CakupanData::PerBidang => $izin !== 'pengaduan.lihat'
                    || ($bidang !== null && CakupanDataSp::bidangDinas($user)?->value === $bidang),
            })
            ->values();
    }

    public static function admin(?int $kecualiUserId = null): Collection
    {
        return User::query()
            ->where('is_aktif', true)
            ->whereHas('role', fn ($q) => $q->where('is_aktif', true)->where('is_terkunci', true))
            ->when($kecualiUserId, fn ($q) => $q->whereKeyNot($kecualiUserId))
            ->get();
    }
}
