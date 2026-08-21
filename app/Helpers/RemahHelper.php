<?php

namespace App\Helpers;

/**
 * Penyusun remah roti dari struktur menu.
 *
 * DIBACA OTOMATIS DARI `MenuHelper`, bukan ditulis tangan per halaman.
 *
 * Sebelum 2026-08-20 setiap halaman mengarang sendiri ruas pertamanya, dan
 * hasilnya tidak satu pun cocok dengan menu yang benar-benar dipakai: modul
 * transmigran menulis "Kependudukan" padahal menunya "Penduduk & Lahan",
 * poktan menulis "Kelembagaan" padahal menunya "Poktan & Sarana", dan halaman
 * daftar inventaris menulis "Wilayah dan SP" sedangkan halaman rinciannya
 * sendiri menulis "Wilayah dan Aset SP".
 *
 * Remah yang tidak sejalan dengan menu lebih buruk daripada tidak ada remah
 * sama sekali: ia menyatakan pengguna berada pada cabang yang tidak pernah ia
 * lewati, sehingga tombol kembali di kepalanya justru menyesatkan.
 *
 * Bentuknya tiga ruas sesuai permintaan pemilik proyek:
 *
 *     Beranda / {item menu} / {halaman} / {rincian bila ada}
 *
 * Ruas "Beranda" disisipkan komponen `x-sim.page-header`, sehingga helper ini
 * hanya menyusun sisanya. Judul KELOMPOK menu seperti "Transmigrasi" sengaja
 * tidak diikutkan: empat ruas terlalu panjang pada layar sempit, dan yang
 * benar-benar menolong pengguna adalah nama submenu tempat halaman itu hidup.
 */
class RemahHelper
{
    /**
     * Menyusun remah untuk satu halaman berdasarkan alamatnya.
     *
     * @param  string  $path  Alamat halaman daftar, contoh `/transmigran`
     * @param  string|null  $rincian  Label baris yang sedang dibuka, bila ada
     * @return array<int, array<string, string>> Ruas remah setelah Beranda
     */
    public static function untuk(string $path, ?string $rincian = null): array
    {
        $temuan = self::cariPadaMenu($path);

        // Halaman di luar menu tetap mendapat remah yang masuk akal, bukan
        // remah kosong. Contohnya halaman profil dan galeri komponen.
        if ($temuan === null) {
            $remah = [['label' => self::labelDariPath($path)]];

            return self::tutup($remah, $rincian);
        }

        $remah = [];

        // Item bersubmenu menyumbang dua ruas: nama submenunya lalu nama
        // halamannya. Item tanpa submenu, seperti Dashboard, hanya satu.
        if ($temuan['induk'] !== null) {
            $remah[] = ['label' => $temuan['induk']];
        }

        $remah[] = ['label' => $temuan['nama'], 'url' => url($path)];

        return self::tutup($remah, $rincian);
    }

    /**
     * Menutup remah: ruas terakhir tidak boleh bertaut ke dirinya sendiri.
     *
     * @param  array<int, array<string, string>>  $remah  Ruas yang sudah disusun
     * @param  string|null  $rincian  Label rincian bila ada
     * @return array<int, array<string, string>> Remah siap render
     */
    private static function tutup(array $remah, ?string $rincian): array
    {
        if ($rincian !== null) {
            $remah[] = ['label' => $rincian];

            return $remah;
        }

        // Tanpa rincian, halaman daftar itu sendiri yang menjadi ruas
        // terakhir, sehingga tautannya dilepas. Komponen memang sudah tidak
        // merender tautan pada ruas terakhir, tetapi menyisakan `url` di sini
        // membuat larik itu menjanjikan sesuatu yang tidak dipakai.
        $akhir = count($remah) - 1;
        unset($remah[$akhir]['url']);

        return $remah;
    }

    /**
     * Mencari satu alamat pada definisi menu.
     *
     * @param  string  $path  Alamat yang dicari
     * @return array{induk: string|null, nama: string}|null Nama submenu dan halamannya
     */
    private static function cariPadaMenu(string $path): ?array
    {
        foreach (MenuHelper::definisiMenu() as $kelompok) {
            foreach ($kelompok['items'] as $item) {
                if (($item['path'] ?? null) === $path) {
                    return ['induk' => null, 'nama' => $item['name']];
                }

                foreach ($item['subItems'] ?? [] as $sub) {
                    if (($sub['path'] ?? null) === $path) {
                        return ['induk' => $item['name'], 'nama' => $sub['name']];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Menyusun label dari alamat, untuk halaman yang tidak ada di menu.
     *
     * @param  string  $path  Alamat halaman
     * @return string Label berhuruf kapital di tiap kata
     */
    private static function labelDariPath(string $path): string
    {
        $ruas = trim($path, '/');

        if ($ruas === '') {
            return 'Beranda';
        }

        // Hanya ruas terakhir yang dipakai: `/profil/kata-sandi` menjadi
        // "Kata Sandi", sebab induknya sudah disebut halaman pemanggil.
        $bagian = explode('/', $ruas);

        return ucwords(str_replace('-', ' ', end($bagian)));
    }
}
