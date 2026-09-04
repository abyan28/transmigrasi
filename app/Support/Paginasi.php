<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Validasi jumlah baris per halaman (Fase 1, 2026-09-05, rules.md 13.3 poin 2).
 *
 * Satu sumber dipakai seluruh controller daftar, menggantikan salinan yang
 * sebelumnya hanya ada di `WilayahController::PER_HALAMAN` (satu-satunya
 * controller yang sudah benar sebelum Fase 1). Nilai karangan pada query
 * string (`?per_halaman=999`) jatuh diam-diam ke bawaan 25, bukan galat --
 * paginasi adalah kenyamanan tampilan, bukan input yang perlu divalidasi
 * keras ke pengguna.
 */
class Paginasi
{
    /** @var list<int> */
    public const PILIHAN = [10, 25, 50, 100];

    public static function perHalaman(Request $request, int $bawaan = 25): int
    {
        $nilai = (int) $request->query('per_halaman', $bawaan);

        return in_array($nilai, self::PILIHAN, true) ? $nilai : $bawaan;
    }
}
