<?php

namespace App\Http\Controllers;

use App\Support\ImporEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Menerima unggahan XLSX/CSV dan memprosesnya langsung (Task 10.4, 1/2).
 *
 * Satu rute melayani seluruh entitas yang sudah aktif (`ImporEngine::
 * entitasAktif()`); entitas yang belum dikerjakan (enam entitas berantai)
 * membalas 404 -- modalnya di halaman itu tetap menampilkan spanduk
 * "Fitur belum aktif" (Task 10.6/10.4 sebelumnya).
 *
 * Kewenangan `{modul}.tambah` diperiksa DI SINI, bukan lewat middleware
 * `izin:` otomatis (`bootstrap/app.php`), sebab modulnya berupa parameter
 * rute dinamis -- pola sama dengan `DokumenController::tampilkan`.
 */
class ImporController extends Controller
{
    public function unggah(Request $request, string $entitas): JsonResponse
    {
        abort_unless(ImporEngine::aktif($entitas), 404);

        $modul = ImporEngine::modul($entitas);
        abort_unless(
            $modul !== null
                && $request->user()?->punyaAksi($modul, 'lihat') === true
                && $request->user()?->punyaAksi($modul, 'tambah') === true,
            403,
            'Anda tidak memiliki kewenangan membuka atau menambah data ini.',
        );

        $request->validate([
            'berkas' => ['required', 'file', 'max:5120'],
        ], [
            'berkas.required' => 'Pilih berkas lebih dulu.',
            'berkas.max' => 'Ukuran berkas maksimal 5 MB.',
        ]);

        $berkas = $request->file('berkas');
        $ekstensi = strtolower((string) ($berkas->getClientOriginalExtension() ?: $berkas->extension()));

        if (! in_array($ekstensi, ['xlsx', 'csv'], true)) {
            return response()->json([
                'pesan' => 'Berkas harus berformat XLSX (.xlsx) atau CSV (.csv).',
            ], 422);
        }

        try {
            return response()->json(ImporEngine::proses($entitas, $berkas->getRealPath(), $ekstensi));
        } catch (Throwable $e) {
            if (! $e instanceof \InvalidArgumentException) {
                report($e);
            }

            return response()->json([
                'pesan' => $e instanceof \InvalidArgumentException
                    ? $e->getMessage()
                    : 'Berkas gagal diproses. Pastikan format dan isinya benar.',
            ], 422);
        }
    }
}
