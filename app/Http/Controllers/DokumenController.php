<?php

namespace App\Http\Controllers;

use App\Support\PenyimpananDokumen;
use App\Support\PetaModulBerkas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Melayani pengunduhan dokumen dan foto yang tersimpan di disk privat.
 *
 * Berkas TIDAK boleh diakses langsung lewat URL publik, karena dokumen
 * kependudukan memuat data pribadi. Setiap permintaan wajib melewati dua
 * lapis pemeriksaan lebih dulu (agents/rules.md 14a poin 6):
 *
 * 1. **Kewenangan `lihat`** pada modul pemilik berkas (Task 3.3).
 * 2. **Cakupan data** (Task 10.6): baris pemilik diambil lewat modelnya
 *    sehingga global scope `CakupanDataSp` ikut berlaku -- operator Per SP
 *    tidak dapat membuka berkas SP di luar penugasannya. Peta modul->model
 *    ada di `App\Support\PetaModulBerkas`.
 */
class DokumenController extends Controller
{
    /**
     * Mengalirkan isi berkas ke peramban setelah hak akses diperiksa.
     *
     * Memakai aliran data, bukan memuat seluruh berkas ke memori, agar
     * dokumen berukuran besar tidak membebani server.
     *
     * @param  Request  $request  Permintaan masuk
     * @param  string  $modul  Nama modul, contoh `transmigran`
     * @param  int  $id  Id baris pemilik berkas
     * @param  string  $namaBerkas  Nama berkas yang diminta
     * @return StreamedResponse Aliran isi berkas
     */
    public function tampilkan(Request $request, string $modul, int $id, string $namaBerkas): StreamedResponse
    {
        $path = PenyimpananDokumen::folder($modul, $id).'/'.$namaBerkas;

        // Menolak upaya menembus folder lain lewat penulisan path,
        // misalnya "../../.env" yang diselundupkan pada nama berkas.
        if (str_contains($namaBerkas, '..') || str_contains($namaBerkas, '/') || str_contains($namaBerkas, '\\')) {
            abort(404);
        }

        if (! PenyimpananDokumen::ada($path)) {
            abort(404, 'Dokumen tidak ditemukan.');
        }

        // Kewenangan `lihat` pada modul pemilik berkas (Task 3.3). Diperiksa di
        // sini, bukan lewat middleware `izin:`, sebab modulnya berupa parameter
        // rute yang dinamis.
        abort_unless(
            $request->user()?->punyaAksi($modul, 'lihat') === true,
            403,
            'Anda tidak memiliki kewenangan membuka dokumen ini.',
        );

        // Cakupan data (Task 10.6): baris pemilik diambil lewat modelnya
        // sehingga scope `CakupanDataSp` ikut berlaku. Di luar cakupan -> 404,
        // tak dapat dibedakan dari berkas yang memang tidak ada.
        abort_unless(PetaModulBerkas::pemilikTerlihat($modul, $id), 404, 'Dokumen tidak ditemukan.');

        return Storage::disk(PenyimpananDokumen::DISK)->response($path);
    }
}
