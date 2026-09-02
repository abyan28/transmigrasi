<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Mengelola penyimpanan dokumen dan foto pendukung.
 *
 * Seluruh berkas disimpan pada disk privat `storage/app/private`, tidak pernah
 * di folder publik. Database hanya menyimpan path, bukan isi berkasnya
 * (agents/rules.md bagian 14a).
 *
 * Berkas hanya dapat diunduh lewat controller yang memeriksa hak akses lebih
 * dulu. Ini disengaja: dokumen kependudukan memuat data pribadi, sehingga tidak
 * boleh dapat diakses siapa pun yang menebak alamat URL-nya.
 *
 * Contoh pemakaian:
 *
 *     $path = PenyimpananDokumen::simpan(
 *         berkas: $request->file('dokumen_pendukung'),
 *         modul: 'transmigran',
 *         idPemilik: $transmigran->id_transmigran,
 *         namaDokumen: 'KartuKeluarga',
 *         namaPemilik: $transmigran->nama_kepala_keluarga,
 *     );
 *     // menghasilkan: transmigran/12/KartuKeluarga_yohanes-bere.pdf
 */
class PenyimpananDokumen
{
    /** Nama disk penyimpanan privat, mengacu pada config/filesystems.php. */
    public const DISK = 'local';

    /** Batas ukuran berkas dalam byte, setara 5 MB. */
    public const MAKS_UKURAN_BYTE = 5 * 1024 * 1024;

    /**
     * Ekstensi yang boleh tersimpan ke disk.
     *
     * Dibaca dari ValidationRules agar tidak ada dua daftar yang dapat
     * berselisih diam-diam: nilai yang sama dipakai aturan validasi dan
     * atribut `accept` pada `components/sim/file-upload.blade.php`
     * (agents/rules.md bagian 13.2 poin 6, validasi terpusat).
     *
     * @return array<int, string> Daftar ekstensi huruf kecil
     */
    public static function ekstensiDiterima(): array
    {
        return explode(',', ValidationRules::JENIS_BERKAS);
    }

    /**
     * Menyimpan berkas unggahan dan mengembalikan path relatifnya.
     *
     * Pola penamaan mengikuti agents/rules.md bagian 14a poin 5:
     * `[NamaDokumen]_[nama-pemilik].[ekstensi]`, dengan spasi pada nama pemilik
     * diganti tanda hubung.
     *
     * @param  UploadedFile  $berkas  Berkas hasil unggahan
     * @param  string  $modul  Nama modul, dipakai sebagai folder tingkat pertama
     * @param  int  $idPemilik  Id baris pemilik berkas
     * @param  string  $namaDokumen  Jenis dokumen, contoh `KartuKeluarga`
     * @param  string  $namaPemilik  Nama pemilik untuk melengkapi nama berkas
     * @return string Path relatif terhadap disk privat
     */
    public static function simpan(
        UploadedFile $berkas,
        string $modul,
        int $idPemilik,
        string $namaDokumen,
        string $namaPemilik = ''
    ): string {
        $folder = self::folder($modul, $idPemilik);
        $namaBerkas = self::susunNamaBerkas($berkas, $namaDokumen, $namaPemilik);

        return Storage::disk(self::DISK)->putFileAs($folder, $berkas, $namaBerkas);
    }

    /**
     * Mengganti berkas lama dengan yang baru.
     *
     * Berkas lama dihapus hanya setelah berkas baru berhasil tersimpan, agar
     * kegagalan penyimpanan tidak menyebabkan dokumen hilang sama sekali.
     *
     * @param  UploadedFile  $berkas  Berkas pengganti
     * @param  string|null  $pathLama  Path berkas sebelumnya, boleh kosong
     * @param  string  $modul  Nama modul
     * @param  int  $idPemilik  Id baris pemilik berkas
     * @param  string  $namaDokumen  Jenis dokumen
     * @param  string  $namaPemilik  Nama pemilik
     * @return string Path relatif berkas baru
     */
    public static function ganti(
        UploadedFile $berkas,
        ?string $pathLama,
        string $modul,
        int $idPemilik,
        string $namaDokumen,
        string $namaPemilik = ''
    ): string {
        $pathBaru = self::simpan($berkas, $modul, $idPemilik, $namaDokumen, $namaPemilik);

        if ($pathLama !== null && $pathLama !== $pathBaru) {
            self::hapus($pathLama);
        }

        return $pathBaru;
    }

    /**
     * Menghapus berkas bila ada.
     *
     * @param  string|null  $path  Path relatif berkas
     * @return bool True bila berkas terhapus atau memang tidak ada
     */
    public static function hapus(?string $path): bool
    {
        if ($path === null || $path === '') {
            return true;
        }

        if (! Storage::disk(self::DISK)->exists($path)) {
            return true;
        }

        return Storage::disk(self::DISK)->delete($path);
    }

    /**
     * Menghapus seluruh berkas milik satu baris data.
     *
     * Dipakai saat data dihapus permanen, bukan saat soft delete.
     *
     * @param  string  $modul  Nama modul
     * @param  int  $idPemilik  Id baris pemilik berkas
     * @return bool True bila folder terhapus atau memang tidak ada
     */
    public static function hapusFolder(string $modul, int $idPemilik): bool
    {
        $folder = self::folder($modul, $idPemilik);

        if (! Storage::disk(self::DISK)->exists($folder)) {
            return true;
        }

        return Storage::disk(self::DISK)->deleteDirectory($folder);
    }

    /**
     * Memeriksa keberadaan berkas.
     *
     * @param  string|null  $path  Path relatif berkas
     * @return bool True bila berkas ada
     */
    public static function ada(?string $path): bool
    {
        if ($path === null || $path === '') {
            return false;
        }

        return Storage::disk(self::DISK)->exists($path);
    }

    /**
     * Mengambil ukuran berkas dalam byte.
     *
     * @param  string  $path  Path relatif berkas
     * @return int Ukuran berkas, atau 0 bila tidak ada
     */
    public static function ukuran(string $path): int
    {
        return self::ada($path) ? Storage::disk(self::DISK)->size($path) : 0;
    }

    /**
     * Menyusun path folder penyimpanan.
     *
     * Struktur: `[modul]/[id-pemilik]/`, contoh `transmigran/12/`.
     * Pemisahan per id mencegah satu folder memuat ribuan berkas sekaligus.
     *
     * @param  string  $modul  Nama modul
     * @param  int  $idPemilik  Id baris pemilik berkas
     * @return string Path folder relatif
     */
    public static function folder(string $modul, int $idPemilik): string
    {
        return Str::slug($modul) . '/' . $idPemilik;
    }

    /**
     * Menyusun nama berkas sesuai pola penamaan yang disepakati.
     *
     * Nama pemilik dijadikan huruf kecil berpemisah tanda hubung agar aman
     * dipakai di seluruh sistem berkas, termasuk yang tidak menerima spasi.
     *
     * @param  UploadedFile  $berkas  Berkas unggahan, dipakai mengambil ekstensinya
     * @param  string  $namaDokumen  Jenis dokumen
     * @param  string  $namaPemilik  Nama pemilik
     * @return string Nama berkas lengkap beserta ekstensi
     */
    public static function susunNamaBerkas(UploadedFile $berkas, string $namaDokumen, string $namaPemilik = ''): string
    {
        $ekstensi = self::ekstensiAman($berkas);

        // Spasi dibuang dan tiap kata diawali huruf besar, tetapi huruf besar
        // yang sudah ada di tengah kata dipertahankan. Dengan begitu masukan
        // "Kartu Keluarga" maupun "KartuKeluarga" sama-sama menghasilkan
        // "KartuKeluarga", bukan "Kartukeluarga".
        $dokumen = str_replace(' ', '', ucwords($namaDokumen));

        if ($namaPemilik === '') {
            return $dokumen . '.' . $ekstensi;
        }

        return $dokumen . '_' . Str::slug($namaPemilik) . '.' . $ekstensi;
    }

    /**
     * Menentukan ekstensi berkas yang aman disimpan ke disk.
     *
     * Ekstensi DITEBAK DARI ISI BERKAS lebih dulu, bukan dari nama yang
     * dikirim peramban. getClientOriginalExtension() hanya memenggal nama
     * berkas yang dikendalikan sepenuhnya oleh pengunggah, sehingga berkas
     * apa pun dapat diberi nama sesuatu.pdf maupun sesuatu.php.
     * extension() menebaknya dari tipe MIME hasil pemeriksaan isi berkas,
     * yang tidak dapat dikarang lewat nama.
     *
     * Nama kiriman klien tetap dipakai sebagai cadangan ketika penebakan
     * tidak menghasilkan apa pun, tetapi hasilnya SELALU disaring daftar
     * putih, sehingga cadangan itu tidak membuka kembali celah yang sama.
     *
     * Berkas di luar daftar ditolak, bukan disimpan dengan nama seadanya:
     * berkas yang tersimpan diam-diam akan ditemukan kembali suatu saat
     * oleh kode lain yang belum tentu seketat ini (agents/rules.md 14a
     * poin 2, jenis berkas wajib divalidasi di sisi server).
     *
     * @param  UploadedFile  $berkas  Berkas hasil unggahan
     * @return string Ekstensi huruf kecil yang sudah dipastikan aman
     *
     * @throws InvalidArgumentException Bila jenis berkas tidak diterima
     */
    public static function ekstensiAman(UploadedFile $berkas): string
    {
        $diterima = self::ekstensiDiterima();

        foreach ([$berkas->extension(), $berkas->getClientOriginalExtension()] as $calon) {
            $calon = strtolower(trim((string) $calon));

            if ($calon !== '' && in_array($calon, $diterima, true)) {
                return $calon;
            }
        }

        throw new InvalidArgumentException(
            'Jenis berkas tidak diterima. Yang diterima: ' . implode(', ', $diterima) . '.'
        );
    }
}
