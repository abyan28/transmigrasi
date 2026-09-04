<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

/**
 * Jenis kejadian yang dicatat pada audit log.
 *
 * Daftar nilai baku ada pada agents/data-dictionary.md bagian 11.2.
 */
enum AksiAuditLog: string
{
    use PunyaLabel;

    case Tambah = 'Tambah';
    case Ubah = 'Ubah';
    case Hapus = 'Hapus';
    case Pulihkan = 'Pulihkan';
    case Login = 'Login';
    case Logout = 'Logout';
    case ResetKataSandi = 'Reset Kata Sandi';
    case NonaktifkanAkun = 'Nonaktifkan Akun';
    case AktifkanAkun = 'Aktifkan Akun';
    case UbahIzinRole = 'Ubah Izin Role';
}
