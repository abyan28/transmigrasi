<?php

namespace App\Http\Controllers;

use App\Support\DummyData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Rekap kependudukan kawasan (Task 5.5).
 *
 * PERALIHAN STRUKTURAL SAJA: closure `susunRekapKependudukan` dipindah ke sini,
 * tetapi angka AGREGAT masih dari `DummyData::rekap*`. Rekap ini berskala
 * KAWASAN (~1.140 KK, di-skala per tahun oleh `skalakanSebaranKependudukan()`),
 * bukan penjumlahan delapan baris data contoh. Penggantian ke kueri nyata
 * adalah satu paket dengan Task 9.1 (dashboard data nyata) -- keduanya
 * menunggu data berskala sensus DAN modul lahan/panen (`perSp`).
 *
 * Uji `HalamanTest` mengunci angka sintetis ini (mis. 968 KK pada 2020),
 * sehingga mengubah sumbernya menuntut menulis ulang uji itu lebih dulu.
 */
class KependudukanController extends Controller
{
    public function rekap(Request $request, ?string $kelompok = null): View
    {
        $kelompok ??= (string) $request->query('kelompok', 'tahun');

        $daftarTahun = DummyData::daftarTahunKependudukan();
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
            'perTahun' => DummyData::rekapKependudukan(),
            'perSp' => DummyData::rekapPerSp($tahunDipilih),
            'penghuni' => DummyData::rekapPenghuni($tahunDipilih),
            'pekerjaan' => DummyData::sebaranPekerjaan($tahunDipilih),
            // Berlabel: `sebaranDaerahAsal()` berkunci id kabupaten sejak 2026-09-02.
            'daerahAsal' => DummyData::sebaranDaerahAsalBerlabel($tahunDipilih),
            'pendidikan' => DummyData::sebaranPendidikan($tahunDipilih),
            'ringkasan' => DummyData::ringkasanDashboard(),

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
