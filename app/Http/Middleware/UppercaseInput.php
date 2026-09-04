<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mengubah isian teks pengguna menjadi HURUF KAPITAL secara otomatis.
 *
 * Tujuannya menjaga keseragaman data lapangan. Tanpa ini, satu nama desa bisa
 * tersimpan sebagai "Kapitan Meo", "KAPITAN MEO", dan "kapitan meo" sekaligus,
 * sehingga rekap per wilayah menjadi tidak akurat.
 *
 * Aturan lengkap ada pada agents/rules.md bagian 13.2 poin 4.
 *
 * Beberapa jenis isian sengaja DIKECUALIKAN karena huruf besar justru merusak
 * maknanya, misalnya kata sandi yang peka huruf besar-kecil dan alamat surel
 * yang lazim ditulis huruf kecil.
 */
class UppercaseInput
{
    /**
     * Nama kolom yang tidak boleh diubah huruf besarnya.
     *
     * @var array<int, string>
     */
    protected array $kecualikan = [
        // Kredensial, peka terhadap huruf besar-kecil
        'password',
        'password_confirmation',
        'password_lama',
        'password_baru',
        'password_baru_konfirmasi',
        'kata_sandi',
        'kredensial',
        'token',
        '_token',
        '_method',

        // Alamat surel dan username lazim ditulis huruf kecil
        'email',
        'username',
        'url',
        'tautan',

        // Nilai enum/sistem yang peka huruf besar-kecil: cakupan data role
        // ('Per SP') dan matriks kewenangan ('lihat', 'ubah'). `izin`
        // dikecualikan sebagai SUBPOHON -- seluruh nilai di bawahnya ikut aman.
        'cakupan_data',
        'izin',

        // Penunjuk sasaran, bukan isi data. Dikapitalkan, keduanya tak lagi
        // cocok dengan daftar nilai yang sah dan seluruh penyimpanannya ditolak
        // validasi: 'tingkat' memilih TABEL wilayah (4.1), 'jenis' memilih
        // DAFTAR referensi dan berpasangan dengan ENUM basis data (4.7).
        'tingkat',
        'jenis',
        'jenis_fasilitas',

        // Nilai referensi (Task 4.3/4.4/4.6): TEKS yang dicocokkan ke tabel
        // `referensi` lewat ValidationRules::referensi(). Dikapitalkan, ia
        // tersimpan dengan kapitalisasi berbeda dari daftar pilihannya --
        // lolos validasi (kolasi MySQL case-insensitive) tetapi data tak
        // seragam. Ditegakkan tests/Feature/UppercaseInputTest.php.
        'kondisi',
        'status_hunian',
        'sumber_dana',
        'status_penyerahan',
        'jenis_inventaris',

        // Pemilih laten: kolom SP yang untuk sementara divalidasi sebagai
        // string bebas, tetapi enum-nya (PolaPermukiman / BentukWilayah /
        // TingkatKesuburanTanah) menunggu dipakai Tahap 5+. Ditambahkan lebih
        // dulu agar pola tiga kali di Tahap 4 tidak terulang; penjaga uji buta
        // terhadapnya selama masih string bebas.
        'pola_permukiman',
        'bentuk_wilayah',
        'tingkat_kesuburan_tanah',

        // Nilai enum PHP pada form transmigran & anggota keluarga (Task 5.2):
        // divalidasi `Rule::enum`/`Rule::in`, nilainya Title Case ("Laki-laki",
        // "SMA/SMK", "Belum Sekolah") sehingga dikapitalkan pasti gagal validasi.
        // `hubungan`/`kegiatan` hanya bersarang di `anggota_keluarga[i][...]` --
        // regex penjaga tak menjangkaunya, tetapi middleware tetap perlu ini.
        'jenis_kelamin',
        'agama',
        'pendidikan_terakhir',
        'status_tinggal',
        'hubungan',
        'hubungan_pengganti',
        'kegiatan',
        'status',
        'nasib_ketua_poktan',

        // Teks naratif, huruf kapital seluruhnya menyulitkan pembacaan
        'deskripsi',
        'deskripsi_pengaduan',
        'catatan',
        'catatan_hunian',
        'catatan_penanganan',
        'keterangan',
        'objek_keterangan',
        'alasan',
        'alasan_keluar',
        'alasan_tidak_dihuni',
        'tujuan_pemanfaatan',
    ];

    /**
     * Akhiran nama kolom yang dikecualikan.
     *
     * Kolom berakhiran `_id` berisi kunci relasi, sedangkan `_at` dan `_token`
     * berisi nilai sistem. Semuanya tidak boleh diubah bentuknya.
     *
     * @var array<int, string>
     */
    protected array $kecualikanAkhiran = [
        '_id',
        '_at',
        '_token',
        '_password',
        '_email',
        '_url',
    ];

    /**
     * Menjalankan middleware pada setiap permintaan yang membawa data.
     *
     * @param  Request  $request  Permintaan masuk
     * @param  Closure  $next  Penerus rantai middleware
     * @return Response Tanggapan dari lapisan berikutnya
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Permintaan tanpa badan data tidak perlu diproses.
        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return $next($request);
        }

        $request->merge(
            $this->ubahRekursif($request->all())
        );

        return $next($request);
    }

    /**
     * Menelusuri seluruh isian, termasuk yang bersarang, lalu mengubahnya.
     *
     * @param  array<string, mixed>  $data  Isian yang akan diproses
     * @return array<string, mixed> Isian setelah diubah
     */
    protected function ubahRekursif(array $data): array
    {
        foreach ($data as $kunci => $nilai) {
            if (is_array($nilai)) {
                // Kunci array yang dikecualikan menutup seluruh subpohonnya,
                // mis. `izin[transmigran][0] = 'lihat'` tidak boleh jadi 'LIHAT'.
                if ($this->bolehDiubah((string) $kunci)) {
                    $data[$kunci] = $this->ubahRekursif($nilai);
                }

                continue;
            }

            if (is_string($nilai) && $this->bolehDiubah((string) $kunci)) {
                $data[$kunci] = $this->keHurufKapital($nilai);
            }
        }

        return $data;
    }

    /**
     * Memeriksa apakah sebuah kolom boleh diubah menjadi huruf kapital.
     *
     * @param  string  $kunci  Nama kolom isian
     * @return bool True bila kolom tidak termasuk pengecualian
     */
    protected function bolehDiubah(string $kunci): bool
    {
        $kunciKecil = mb_strtolower($kunci);

        if (in_array($kunciKecil, $this->kecualikan, true)) {
            return false;
        }

        foreach ($this->kecualikanAkhiran as $akhiran) {
            if (str_ends_with($kunciKecil, $akhiran)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Mengubah teks menjadi huruf kapital dengan tetap menjaga karakter non-ASCII.
     *
     * Memakai mb_strtoupper agar huruf beraksen tetap terproses dengan benar.
     * Spasi berlebih di awal dan akhir sekaligus dibersihkan.
     *
     * @param  string  $nilai  Teks asal
     * @return string Teks dalam huruf kapital
     */
    protected function keHurufKapital(string $nilai): string
    {
        return mb_strtoupper(trim($nilai), 'UTF-8');
    }
}
