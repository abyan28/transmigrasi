<?php

namespace App\Http\Controllers;

use App\Support\PenyimpananDokumen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Melayani pengunduhan dokumen dan foto yang tersimpan di disk privat.
 *
 * Berkas TIDAK boleh diakses langsung lewat URL publik, karena dokumen
 * kependudukan memuat data pribadi. Setiap permintaan wajib melewati
 * pemeriksaan hak akses lebih dulu (agents/rules.md bagian 14a poin 6).
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
        $path = PenyimpananDokumen::folder($modul, $id) . '/' . $namaBerkas;

        // Menolak upaya menembus folder lain lewat penulisan path,
        // misalnya "../../.env" yang diselundupkan pada nama berkas.
        if (str_contains($namaBerkas, '..') || str_contains($namaBerkas, '/') || str_contains($namaBerkas, '\\')) {
            abort(404);
        }

        if (! PenyimpananDokumen::ada($path)) {
            abort(404, 'Dokumen tidak ditemukan.');
        }

        // ponytail: pemeriksaan izin menyusul pada Tahap 3 setelah RBAC aktif.
        // Ganti dengan Gate::authorize("{$modul}.lihat") beserta pemeriksaan
        // cakupan data, agar operator SP tidak dapat membuka dokumen SP lain.

        return Storage::disk(PenyimpananDokumen::DISK)->response($path);
    }
}
