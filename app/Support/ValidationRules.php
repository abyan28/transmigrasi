<?php

namespace App\Support;

use App\Enums\JenisReferensi;
use Illuminate\Validation\Rule;

/**
 * Kumpulan aturan validasi yang dipakai berulang di banyak form.
 *
 * Seluruh aturan ditulis satu kali di sini agar tidak ada regex yang disalin
 * ke banyak tempat (agents/rules.md bagian 13.2 poin 6). Bila suatu aturan
 * berubah, cukup diubah di berkas ini.
 *
 * Rincian tiap aturan ada pada agents/data-dictionary.md bagian 12.
 *
 * Contoh pemakaian pada Form Request:
 *
 *     public function rules(): array
 *     {
 *         return [
 *             'nik'   => ValidationRules::nik(abaikanId: $this->transmigran?->id_transmigran),
 *             'nama'  => ValidationRules::nama(),
 *             'luas'  => ValidationRules::luas(),
 *         ];
 *     }
 *
 *     public function messages(): array
 *     {
 *         return ValidationRules::pesan();
 *     }
 */
class ValidationRules
{
    /** Batas ukuran unggahan dalam kilobyte, setara 5 MB (agents/rules.md bagian 14a). */
    public const MAKS_UKURAN_BERKAS_KB = 5120;

    /** Jenis berkas yang diterima untuk dokumen pendukung. */
    public const JENIS_BERKAS = 'jpg,jpeg,png,webp,pdf';

    /**
     * Aturan NIK: tepat 16 digit angka dan unik pada tabel yang bersangkutan.
     *
     * @param  string  $tabel  Nama tabel yang diperiksa keunikannya
     * @param  string  $kolom  Nama kolom NIK pada tabel tersebut
     * @param  int|null  $abaikanId  Id baris yang dikecualikan saat mengubah data
     * @param  string  $primaryKey  Nama primary key tabel, mengikuti konvensi `id_namatabel`
     * @return array<int, string> Daftar aturan siap pakai
     */
    public static function nik(string $tabel = 'transmigran', string $kolom = 'nik', ?int $abaikanId = null, string $primaryKey = 'id_transmigran'): array
    {
        return [
            'required',
            'digits:16',
            self::aturanUnik($tabel, $kolom, $abaikanId, $primaryKey),
        ];
    }

    /**
     * Aturan nomor Kartu Keluarga: tepat 16 digit angka dan unik.
     *
     * @param  int|null  $abaikanId  Id transmigran yang dikecualikan saat mengubah data
     * @return array<int, string> Daftar aturan siap pakai
     */
    public static function noKk(?int $abaikanId = null): array
    {
        return [
            'required',
            'digits:16',
            self::aturanUnik('transmigran', 'no_kk', $abaikanId, 'id_transmigran'),
        ];
    }

    /**
     * Aturan nama orang: 3 sampai 255 karakter, hanya huruf, spasi, titik, dan apostrof.
     *
     * Angka sengaja ditolak agar kesalahan ketik seperti memasukkan NIK ke kolom
     * nama langsung tertangkap.
     *
     * @param  bool  $wajib  Menentukan apakah kolom harus diisi
     * @return array<int, string> Daftar aturan siap pakai
     */
    public static function nama(bool $wajib = true): array
    {
        return [
            $wajib ? 'required' : 'nullable',
            'string',
            'min:3',
            'max:255',
            "regex:/^[\pL\s.']+$/u",
        ];
    }

    /**
     * Aturan teks bebas seperti nama barang atau nama fasilitas.
     *
     * Berbeda dari nama orang, aturan ini memperbolehkan angka dan tanda hubung
     * karena nama barang lazim memuat keduanya, contoh "Traktor Roda 4".
     *
     * @param  bool  $wajib  Menentukan apakah kolom harus diisi
     * @param  int  $maks  Panjang maksimal karakter
     * @return array<int, string> Daftar aturan siap pakai
     */
    public static function teks(bool $wajib = true, int $maks = 255): array
    {
        return [
            $wajib ? 'required' : 'nullable',
            'string',
            'max:'.$maks,
        ];
    }

    /**
     * Aturan nomor telepon Indonesia: 10 sampai 15 digit, diawali 08 atau +62.
     *
     * @param  bool  $wajib  Menentukan apakah kolom harus diisi
     * @return array<int, string> Daftar aturan siap pakai
     */
    public static function telepon(bool $wajib = false): array
    {
        return [
            $wajib ? 'required' : 'nullable',
            'string',
            'regex:/^(08|\+62)[0-9]{8,13}$/',
        ];
    }

    /**
     * Aturan isian kredensial pada halaman masuk: satu kolom menerima email
     * ATAU username (`rules.md` 14b poin 4). Tidak diperiksa formatnya di sini
     * -- pencocokan dilakukan terhadap dua kolom sekaligus di controller, dan
     * pesan galat sengaja tidak membeda-bedakan agar halaman ini tidak menjadi
     * alat memeriksa akun mana yang terdaftar.
     *
     * @return array<int, string> Daftar aturan siap pakai
     */
    public static function kredensialMasuk(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Aturan alamat surel, unik pada tabel pengguna.
     *
     * @param  int|null  $abaikanId  Id pengguna yang dikecualikan saat mengubah data
     * @return array<int, string> Daftar aturan siap pakai
     */
    public static function email(?int $abaikanId = null): array
    {
        return [
            'required',
            'email:rfc',
            'max:255',
            self::aturanUnik('user', 'email', $abaikanId, 'id_user'),
        ];
    }

    /**
     * Aturan username: huruf kecil, angka, titik, dan garis bawah saja.
     *
     * @param  int|null  $abaikanId  Id pengguna yang dikecualikan saat mengubah data
     * @return array<int, string> Daftar aturan siap pakai
     */
    public static function username(?int $abaikanId = null): array
    {
        return [
            'required',
            'string',
            'min:3',
            'max:50',
            'regex:/^[a-z0-9._]+$/',
            self::aturanUnik('user', 'username', $abaikanId, 'id_user'),
        ];
    }

    /**
     * Aturan kata sandi: minimal 8 karakter serta memuat huruf dan angka.
     *
     * @param  bool  $wajib  Diisi false pada form ubah data, agar kata sandi lama dipertahankan bila dikosongkan
     * @param  bool  $konfirmasi  Sertakan aturan `confirmed` (mensyaratkan kolom `<nama>_confirmation`). Dimatikan bila kolom ulangannya bernama lain dan diperiksa lewat `same:`.
     * @return array<int, string> Daftar aturan siap pakai
     */
    public static function password(bool $wajib = true, bool $konfirmasi = true): array
    {
        return array_values(array_filter([
            $wajib ? 'required' : 'nullable',
            'string',
            'min:8',
            'max:255',
            'regex:/[A-Za-z]/',
            'regex:/[0-9]/',
            $konfirmasi ? 'confirmed' : null,
        ]));
    }

    /**
     * Aturan tahun: empat digit, antara 1900 sampai tahun berjalan.
     *
     * @param  bool  $wajib  Menentukan apakah kolom harus diisi
     * @return array<int, string> Daftar aturan siap pakai
     */
    public static function tahun(bool $wajib = false): array
    {
        return [
            $wajib ? 'required' : 'nullable',
            'integer',
            'min:1900',
            'max:'.date('Y'),
        ];
    }

    /**
     * Aturan luas lahan dalam hektare: lebih besar dari nol, maksimal 2 desimal.
     *
     * @param  bool  $wajib  Menentukan apakah kolom harus diisi
     * @return array<int, string> Daftar aturan siap pakai
     */
    public static function luas(bool $wajib = true): array
    {
        return [
            $wajib ? 'required' : 'nullable',
            'numeric',
            'gt:0',
            'max:9999999999',
            'decimal:0,2',
        ];
    }

    /**
     * Aturan volume panen: lebih besar dari nol, maksimal 3 desimal.
     *
     * Presisi 3 desimal dipakai agar panen berskala kecil tetap terekam,
     * misalnya 0,001 ton yang setara 1 kilogram (agents/rules.md bagian 9).
     *
     * @param  bool  $wajib  Menentukan apakah kolom harus diisi
     * @return array<int, string> Daftar aturan siap pakai
     */
    public static function volume(bool $wajib = true): array
    {
        return [
            $wajib ? 'required' : 'nullable',
            'numeric',
            'gt:0',
            'max:999999999',
            'decimal:0,3',
        ];
    }

    /**
     * Aturan nilai uang dalam rupiah: bilangan bulat, tidak boleh negatif.
     *
     * @param  bool  $wajib  Menentukan apakah kolom harus diisi
     * @return array<int, string> Daftar aturan siap pakai
     */
    public static function uang(bool $wajib = false): array
    {
        return [
            $wajib ? 'required' : 'nullable',
            'numeric',
            'min:0',
            'max:999999999999',
        ];
    }

    /**
     * Aturan lintang: antara -90 sampai 90 derajat.
     *
     * @return array<int, string> Daftar aturan siap pakai
     */
    public static function lintang(): array
    {
        return ['nullable', 'numeric', 'between:-90,90'];
    }

    /**
     * Aturan bujur: antara -180 sampai 180 derajat.
     *
     * @return array<int, string> Daftar aturan siap pakai
     */
    public static function bujur(): array
    {
        return ['nullable', 'numeric', 'between:-180,180'];
    }

    /**
     * Aturan unggahan dokumen pendukung: maksimal 5 MB, berupa gambar atau PDF.
     *
     * @param  bool  $wajib  Menentukan apakah berkas harus diunggah
     * @return array<int, string> Daftar aturan siap pakai
     */
    public static function dokumen(bool $wajib = false): array
    {
        return [
            $wajib ? 'required' : 'nullable',
            'file',
            'mimes:'.self::JENIS_BERKAS,
            'max:'.self::MAKS_UKURAN_BERKAS_KB,
        ];
    }

    /**
     * Aturan unggahan foto: sama seperti dokumen, tetapi menolak PDF.
     *
     * @param  bool  $wajib  Menentukan apakah berkas harus diunggah
     * @return array<int, string> Daftar aturan siap pakai
     */
    public static function foto(bool $wajib = false): array
    {
        return [
            $wajib ? 'required' : 'nullable',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:'.self::MAKS_UKURAN_BERKAS_KB,
        ];
    }

    /**
     * Menyusun aturan unik yang mengabaikan baris tertentu saat mengubah data.
     *
     * Diperlukan karena konvensi primary key proyek ini adalah `id_namatabel`,
     * bukan `id` seperti asumsi bawaan Laravel (agents/rules.md bagian 4.0).
     *
     * @param  string  $tabel  Nama tabel
     * @param  string  $kolom  Nama kolom yang harus unik
     * @param  int|null  $abaikanId  Nilai primary key yang dikecualikan
     * @param  string  $primaryKey  Nama kolom primary key
     * @return string Aturan unique siap pakai
     */
    protected static function aturanUnik(string $tabel, string $kolom, ?int $abaikanId, string $primaryKey): string
    {
        $aturan = "unique:{$tabel},{$kolom}";

        if ($abaikanId !== null) {
            $aturan .= ",{$abaikanId},{$primaryKey}";
        }

        return $aturan;
    }

    /**
     * Pesan galat berbahasa Indonesia untuk seluruh aturan di atas.
     *
     * Pesan ditulis dengan bahasa yang dimengerti operator lapangan, bukan
     * istilah teknis (agents/rules.md bagian 13.3 poin 7).
     *
     * @return array<string, string> Peta aturan ke pesan galat
     */
    public static function pesan(): array
    {
        return [
            'nik.required' => 'NIK wajib diisi.',
            'nik.digits' => 'NIK harus 16 digit angka.',
            'nik.unique' => 'NIK ini sudah terdaftar pada data lain.',

            'no_kk.required' => 'Nomor KK wajib diisi.',
            'no_kk.digits' => 'Nomor KK harus 16 digit angka.',
            'no_kk.unique' => 'Nomor KK ini sudah terdaftar pada data lain.',

            'nama.required' => 'Nama wajib diisi.',
            'nama.min' => 'Nama minimal 3 huruf.',
            'nama.max' => 'Nama maksimal 255 huruf.',
            'nama.regex' => 'Nama hanya boleh berisi huruf, spasi, titik, dan tanda petik.',

            'telepon.regex' => 'Nomor telepon harus diawali 08 atau +62, dengan panjang 10 sampai 15 digit.',

            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak benar. Contoh: nama@instansi.go.id',
            'email.unique' => 'Email ini sudah dipakai akun lain.',

            'username.required' => 'Username wajib diisi.',
            'username.min' => 'Username minimal 3 karakter.',
            'username.max' => 'Username maksimal 50 karakter.',
            'username.regex' => 'Username hanya boleh berisi huruf kecil, angka, titik, dan garis bawah.',
            'username.unique' => 'Username ini sudah dipakai akun lain.',

            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.regex' => 'Kata sandi harus memuat huruf dan angka.',
            'password.confirmed' => 'Ulangi kata sandi belum sama.',

            'tahun.integer' => 'Tahun harus berupa angka.',
            'tahun.min' => 'Tahun tidak boleh sebelum 1900.',
            'tahun.max' => 'Tahun tidak boleh melebihi tahun ini.',

            'luas.numeric' => 'Luas harus berupa angka.',
            'luas.gt' => 'Luas harus lebih dari 0.',
            'luas.decimal' => 'Luas maksimal 2 angka di belakang koma.',

            'volume.numeric' => 'Volume panen harus berupa angka.',
            'volume.gt' => 'Volume panen harus lebih dari 0.',
            'volume.decimal' => 'Volume panen maksimal 3 angka di belakang koma.',

            'uang.numeric' => 'Nilai harus berupa angka.',
            'uang.min' => 'Nilai tidak boleh negatif.',

            'lintang.between' => 'Lintang harus antara -90 sampai 90.',
            'bujur.between' => 'Bujur harus antara -180 sampai 180.',

            'dokumen.file' => 'Dokumen gagal diunggah. Silakan pilih berkas lagi.',
            'dokumen.mimes' => 'Dokumen harus berupa gambar (JPG, PNG, WEBP) atau PDF.',
            'dokumen.max' => 'Ukuran dokumen maksimal 5 MB.',

            'foto.image' => 'Berkas yang diunggah harus berupa gambar.',
            'foto.mimes' => 'Foto harus berformat JPG, PNG, atau WEBP.',
            'foto.max' => 'Ukuran foto maksimal 5 MB.',
        ];
    }

    /**
     * Nama kolom dalam Bahasa Indonesia untuk pesan galat bawaan Laravel.
     *
     * Dipakai agar pesan seperti "The nama_kepala_keluarga field is required"
     * berubah menjadi "Nama kepala keluarga wajib diisi".
     *
     * @return array<string, string> Peta nama kolom ke label
     */
    public static function label(): array
    {
        return [
            'nik' => 'NIK',
            'no_kk' => 'nomor KK',
            'nama' => 'nama',
            'nama_kepala_keluarga' => 'nama kepala keluarga',
            'telepon' => 'nomor telepon',
            'email' => 'email',
            'username' => 'username',
            'password' => 'kata sandi',
            'tahun_perolehan' => 'tahun perolehan',
            'tahun_kedatangan' => 'tahun kedatangan',
            'luas' => 'luas',
            'volume' => 'volume panen',
            'harga_jual' => 'harga jual',
            'pendapatan_per_bulan' => 'pendapatan per bulan',
            'lintang' => 'lintang',
            'bujur' => 'bujur',
            'dokumen_pendukung' => 'dokumen pendukung',
            'satuan_permukiman_id' => 'satuan permukiman',
            'kawasan_id' => 'kawasan transmigrasi',
            'desa_id' => 'desa',
            'role_id' => 'role',
        ];
    }

    /**
     * Aturan kolom REF: teks yang wajib ada pada daftar pilihan yang AKTIF.
     *
     * Kolom semacam `kondisi`, `sumber_dana`, dan `jenis_inventaris` disimpan
     * TEKS, bukan enum PHP, sebab Admin boleh menambah nilainya lewat menu
     * Daftar Pilihan (Task 4.7). Validasinya karena itu menengok tabel
     * `referensi`, bukan daftar tetap di dalam kode.
     *
     * Hanya baris ber-`is_aktif` yang diterima: nilai yang sudah dinonaktifkan
     * tetap terbaca pada data lama, tetapi tidak boleh dipakai pada data baru.
     *
     * @param  bool  $wajib  Menentukan apakah kolom harus diisi
     * @return array<int, mixed> Daftar aturan siap pakai
     */
    public static function referensi(JenisReferensi $jenis, bool $wajib = false): array
    {
        return [
            $wajib ? 'required' : 'nullable',
            'string',
            'max:100',
            Rule::exists('referensi', 'nilai')
                ->where('jenis', $jenis->value)
                ->where('is_aktif', true),
        ];
    }
}
