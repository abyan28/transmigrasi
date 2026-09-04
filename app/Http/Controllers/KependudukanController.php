<?php

namespace App\Http\Controllers;

use App\Models\Transmigran;
use App\Support\RekapDashboard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Rekap kependudukan kawasan (Task 5.5, data nyata sejak Task 9.1 2026-09-04).
 *
 * Angka dari `App\Support\RekapDashboard`, TAKSIRAN kumulatif dari
 * `transmigran.tahun_kedatangan`/`tahun_keluar` -- bukan potret riwayat
 * sungguhan (`transmigran` tabel keadaan-sekarang). Lihat docblock
 * `RekapDashboard::hadirPadaTahun()`. `pendapatan_rata_rata` per tahun
 * DICABUT dari tabel deret: kolom itu tak dapat direkonstruksi per tahun.
 */
class KependudukanController extends Controller
{
    public function rekap(Request $request, ?string $kelompok = null): View
    {
        $kelompok ??= (string) $request->query('kelompok', 'tahun');

        $daftarTahun = range((int) (Transmigran::min('tahun_kedatangan') ?? date('Y')), (int) date('Y'));
        $tahunTerakhir = end($daftarTahun);
        $tahunDipilih = (int) $request->query('tahun', $tahunTerakhir);

        if (! in_array($tahunDipilih, $daftarTahun, true)) {
            $tahunDipilih = $tahunTerakhir;
        }

        return view('pages.kependudukan.rekap', [
            'title' => 'Rekap Kependudukan',
            'kelompok' => $kelompok,
            'daftarTahun' => $daftarTahun,
            'tahunPilihan' => $tahunDipilih,
            'tahunTerakhir' => $tahunTerakhir,
            'perTahun' => RekapDashboard::perTahun(),
            'perSp' => RekapDashboard::perSp($tahunDipilih),
            'penghuni' => RekapDashboard::penghuniPerTahun($tahunDipilih),
            'pekerjaan' => RekapDashboard::pekerjaanPerTahun($tahunDipilih),
            'daerahAsal' => RekapDashboard::daerahAsalPerTahun($tahunDipilih),
            'pendidikan' => RekapDashboard::pendidikanPerTahun($tahunDipilih),
            'ringkasan' => RekapDashboard::ringkasan(),

            // WAJIB sejalan dengan `where` rute `kependudukan.rekap.kelompok`
            // dan larik `DaftarTautanStatis`. Mengubah salah satunya saja
            // membuat halaman terbit membalas 404 tanpa penjaga apa pun.
            'labelKelompok' => [
                'tahun' => 'Tahun',
                'sp' => 'Satuan Permukiman',
                'status' => 'Status Tinggal',
                'pekerjaan' => 'Pekerjaan',
                'asal' => 'Daerah Asal',
                'pendidikan' => 'Pendidikan',
            ],
        ]);
    }
}
