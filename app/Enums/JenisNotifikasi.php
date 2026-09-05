<?php

namespace App\Enums;

use App\Enums\Concerns\PunyaLabel;

enum JenisNotifikasi: string
{
    use PunyaLabel;

    case PengaduanBaru = 'Pengaduan Baru';
    case PengaduanMendesak = 'Pengaduan Mendesak';
    case SpPerluPenanganan = 'SP Perlu Penanganan';
    case InfrastrukturRusakBerat = 'Infrastruktur Rusak Berat';
    case AkunPengguna = 'Akun Pengguna';
}
