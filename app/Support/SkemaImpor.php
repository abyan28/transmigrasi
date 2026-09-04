<?php

namespace App\Support;

use App\Enums\Agama;
use App\Enums\AsalWakilPoktan;
use App\Enums\JenisFasilitas;
use App\Enums\JenisKelamin;
use App\Enums\JenisSaprotan;
use App\Enums\PendidikanTerakhir;
use App\Enums\StatusSertifikat;
use App\Enums\StatusTinggal;
use App\Enums\TipeKomoditas;

/**
 * Skema kolom template impor luring (Task 10.6 / 10.4).
 *
 * PRD 8.1: sinyal di lokus tidak stabil, sehingga petugas mengunduh template,
 * mengisinya luring, lalu mengunggahnya kembali. `rules.md` 12.13: template
 * TIDAK diletakkan di halaman laporan melainkan menjadi langkah pertama modal
 * impor tiap modul -- dan menyediakannya di dua tempat berarti dua berkas yang
 * dapat berbeda diam-diam. Kelas ini adalah SATU sumber susunan kolom itu:
 * dipakai pembangkit template (`TemplateImporController`) dan kelak pembaca
 * unggahannya (Task 10.4).
 *
 * Kolom relasi ditulis dengan NAMA, bukan id: petugas di lapangan tahu "SP
 * Kapitan Meo", bukan angka 1. Kolom berkas (foto, dokumen) TIDAK ada di
 * template -- berkas diunggah lewat form, bukan lewat impor massal.
 *
 * Bentuk tiap kolom: ['kolom', 'wajib' => bool, 'contoh' => string,
 * 'keterangan' => string, 'opsi' => list<string>|null]. `opsi` yang null
 * berarti teks/angka bebas; selain itu daftar nilai baku (enum atau daftar
 * pilihan) yang ditempel `TemplateImporController` sebagai catatan.
 */
class SkemaImpor
{
    /**
     * Entitas yang punya template, sekaligus urutan validasi rutenya.
     *
     * @return list<string>
     */
    public static function entitas(): array
    {
        return array_keys(self::PETA);
    }

    public static function ada(string $entitas): bool
    {
        return array_key_exists($entitas, self::PETA);
    }

    public static function judul(string $entitas): string
    {
        return self::PETA[$entitas]['judul'] ?? ucfirst(str_replace('-', ' ', $entitas));
    }

    /**
     * @return list<array{kolom: string, wajib: bool, contoh: string, keterangan: string, opsi: list<string>|null}>
     */
    public static function kolom(string $entitas): array
    {
        $def = self::PETA[$entitas]['kolom'] ?? [];

        return array_map(fn (array $k): array => [
            'kolom' => $k[0],
            'wajib' => $k[1],
            'contoh' => $k[2],
            'keterangan' => $k[3],
            'opsi' => $k[4] ?? null,
        ], $def);
    }

    /**
     * Nilai enum sebagai daftar string, untuk kolom `opsi` bertanda `enum:*`.
     *
     * @return list<string>
     */
    public static function opsiEnum(string $kunci): array
    {
        return match ($kunci) {
            'jenis_kelamin' => array_map(fn (JenisKelamin $c) => $c->value, JenisKelamin::cases()),
            'agama' => array_map(fn (Agama $c) => $c->value, Agama::cases()),
            'pendidikan_terakhir' => array_map(fn (PendidikanTerakhir $c) => $c->value, PendidikanTerakhir::cases()),
            'status_tinggal' => array_map(fn (StatusTinggal $c) => $c->value, StatusTinggal::cases()),
            'status_sertifikat' => array_map(fn (StatusSertifikat $c) => $c->value, StatusSertifikat::cases()),
            'jenis_saprotan' => array_map(fn (JenisSaprotan $c) => $c->value, JenisSaprotan::cases()),
            'asal_ketua' => array_map(fn (AsalWakilPoktan $c) => $c->value, AsalWakilPoktan::cases()),
            'jenis_fasilitas' => array_map(fn (JenisFasilitas $c) => $c->value, JenisFasilitas::cases()),
            'tipe_komoditas' => array_map(fn (TipeKomoditas $c) => $c->value, TipeKomoditas::cases()),
            default => [],
        };
    }

    /**
     * Kolom yang nilainya dari daftar pilihan (DB) -- kuncinya `JenisDaftarPilihan`.
     * `TemplateImporController` yang menyelesaikannya jadi daftar nilai.
     *
     * @return array<string, string> nama kolom => nama case JenisDaftarPilihan
     */
    public static function kolomDaftarPilihan(string $entitas): array
    {
        return self::PETA[$entitas]['daftarPilihan'] ?? [];
    }

    private const T = 'teks bebas';

    /**
     * @var array<string, array{judul: string, kolom: list<array{0:string,1:bool,2:string,3:string,4?:list<string>}>, daftarPilihan?: array<string,string>}>
     */
    private const PETA = [
        'transmigran' => [
            'judul' => 'Data Transmigran',
            'kolom' => [
                ['nik', true, '5321011505800001', 'NIK 16 digit, unik'],
                ['nama_lengkap', true, 'YOHANES BERE', 'Nama kepala keluarga'],
                ['no_kk', true, '5321010102150001', 'Nomor Kartu Keluarga 16 digit'],
                ['satuan_permukiman', true, 'SP Kapitan Meo', 'Nama SP penempatan (harus sudah terdaftar)'],
                ['jenis_kelamin', false, 'Laki-laki', 'Salah satu nilai baku', ['enum:jenis_kelamin']],
                ['agama', false, 'Katolik', 'Salah satu nilai baku', ['enum:agama']],
                ['tempat_lahir', false, 'KUPANG', self::T],
                ['tanggal_lahir', false, '1980-05-15', 'Format YYYY-MM-DD'],
                ['pendidikan_terakhir', false, 'SMA/SMK', 'Salah satu nilai baku', ['enum:pendidikan_terakhir']],
                ['pekerjaan', true, 'PETANI', 'Pekerjaan kepala keluarga'],
                ['pendapatan_per_bulan', false, '2350000', 'Angka rupiah tanpa titik'],
                ['daerah_asal_kabupaten', false, 'Kota Kupang', 'Nama kabupaten/kota asal'],
                ['tahun_kedatangan', true, '2016', 'Tahun (YYYY)'],
                ['status_tinggal', true, 'Aktif', 'Salah satu nilai baku', ['enum:status_tinggal']],
                ['telepon', false, '081234567801', self::T],
                ['keterangan', false, '', self::T],
            ],
        ],
        'rumah' => [
            'judul' => 'Data Rumah',
            'kolom' => [
                ['kode_rumah', true, 'A-01', 'Nomor/kode rumah, unik per SP'],
                ['satuan_permukiman', true, 'SP Kapitan Meo', 'Nama SP'],
                ['nik_penghuni', false, '5321011505800001', 'NIK KK penghuni; kosongkan bila rumah tidak dihuni'],
                ['kondisi', true, 'Tidak Rusak', 'Nilai baku kondisi rumah', ['dp']],
                ['status_hunian', true, 'Dihuni', 'Nilai baku status hunian', ['dp']],
                ['alasan_tidak_dihuni', false, '', 'Wajib bila status hunian = Tidak Dihuni'],
                ['tahun_pembangunan', false, '2016', 'Tahun (YYYY)'],
                ['luas_bangunan', false, '36', 'Meter persegi'],
                ['lintang', false, '-9.51241', 'Desimal derajat'],
                ['bujur', false, '124.91242', 'Desimal derajat'],
                ['catatan_hunian', false, '', self::T],
            ],
            'daftarPilihan' => ['kondisi' => 'KondisiRumah', 'status_hunian' => 'StatusHunian'],
        ],
        'lahan' => [
            'judul' => 'Data Lahan',
            'kolom' => [
                ['kode_lahan', true, 'LH-001', 'Kode bidang lahan, unik'],
                ['nik_pemilik', true, '5321011505800001', 'NIK kepala keluarga pemilik'],
                ['satuan_permukiman', true, 'SP Kapitan Meo', 'Nama SP'],
                ['luas_pekarangan', false, '0.25', 'Hektare; kosongkan bila belum menerima'],
                ['luas_usaha', false, '1.50', 'Hektare; kosongkan bila belum menerima'],
                ['luas_kering', false, '1.50', 'Hektare (bagian dari luas usaha)'],
                ['luas_basah', false, '0', 'Hektare (bagian dari luas usaha)'],
                ['status_sertifikat', true, 'Sudah', 'Nilai baku', ['enum:status_sertifikat']],
                ['lintang_usaha', false, '-9.5138', 'Desimal derajat'],
                ['bujur_usaha', false, '124.9152', 'Desimal derajat'],
                ['keterangan', false, '', self::T],
            ],
        ],
        'poktan' => [
            'judul' => 'Kelompok Tani',
            'kolom' => [
                ['nama_poktan', true, 'POKTAN MEKAR JAYA', 'Nama kelompok, unik'],
                ['satuan_permukiman', true, 'SP Kapitan Meo', 'Nama SP'],
                ['tahun_berdiri', false, '2016', 'Tahun (YYYY)'],
                ['asal_ketua', true, 'Kepala Keluarga', 'Nilai baku', ['enum:asal_ketua']],
                ['nik_ketua', false, '5321011505800001', 'Wajib bila asal ketua = Kepala Keluarga / Anggota Keluarga'],
                ['nama_ketua', false, 'YOSEPH KLAU', 'Wajib bila asal ketua = Bukan Transmigran'],
                ['telepon_ketua', false, '081234567801', self::T],
                ['email_ketua', false, '', self::T],
                ['alamat_ketua', false, '', self::T],
                ['luas_kering_ketua', false, '', 'Hektare; hanya bila asal ketua = Bukan Transmigran'],
                ['luas_basah_ketua', false, '', 'Hektare; hanya bila asal ketua = Bukan Transmigran'],
                ['keterangan', false, '', self::T],
            ],
        ],
        'alsintan' => [
            'judul' => 'Data Alsintan',
            'kolom' => [
                ['jenis_alsintan', true, 'Traktor Roda Dua', 'Nilai baku jenis alsintan', ['dp']],
                ['nama_alat', true, 'Quick G1000 Boxer', self::T],
                ['jumlah_total', true, '4', 'Jumlah unit pengadaan'],
                ['tahun_pengadaan', true, '2023', 'Tahun anggaran (YYYY)'],
                ['sumber_dana', false, 'APBN', 'Nilai baku sumber dana', ['dp']],
                ['keterangan', false, '', self::T],
            ],
            'daftarPilihan' => ['jenis_alsintan' => 'JenisAlsintan', 'sumber_dana' => 'SumberDana'],
        ],
        'saprotan' => [
            'judul' => 'Data Saprotan',
            'kolom' => [
                ['jenis_saprotan', true, 'Benih', 'Nilai baku jenis saprotan', ['enum:jenis_saprotan']],
                ['nama', true, 'BENIH JAGUNG HIBRIDA', self::T],
                ['jumlah_total', true, '250', 'Angka'],
                ['satuan', true, 'Kilogram', 'Nama satuan (harus terdaftar di Data Master Satuan)'],
                ['tahun_pengadaan', true, '2025', 'Tahun anggaran (YYYY)'],
                ['komoditas', false, 'JAGUNG', 'Wajib bila jenis = Benih; nama komoditas terdaftar'],
                ['varietas', false, 'Hibrida Bisi-18', 'Wajib bila jenis = Benih'],
                ['jadwal_tanam', false, '2026-02', 'Rencana, format YYYY-MM'],
                ['sumber_dana', false, 'APBD Provinsi', 'Nilai baku', ['dp']],
                ['keterangan', false, '', self::T],
            ],
            'daftarPilihan' => ['sumber_dana' => 'SumberDana'],
        ],
        'komoditas' => [
            'judul' => 'Data Komoditas',
            'kolom' => [
                ['nama_komoditas', true, 'JAGUNG', 'Nama komoditas, unik'],
                ['jenis', true, 'Pangan', 'Nilai baku tipe komoditas', ['enum:tipe_komoditas']],
                ['satuan_baku', true, 'Ton', 'Nama satuan panen baku (harus terdaftar)'],
                ['unggulan', false, 'tidak', 'ya / tidak'],
                ['deskripsi', false, '', self::T],
            ],
        ],
        'penanaman' => [
            'judul' => 'Penanaman',
            'kolom' => [
                ['kelompok_tani', true, 'POKTAN MEKAR JAYA', 'Nama poktan terdaftar'],
                ['komoditas', true, 'JAGUNG', 'Nama komoditas terdaftar'],
                ['nama_benih', true, 'BENIH JAGUNG HIBRIDA', 'Nama pengadaan benih yang jatahnya dipakai'],
                ['periode_tanam', true, '2026-02', 'Format YYYY-MM'],
                ['volume_benih', true, '150', 'Sesuai satuan benihnya'],
                ['realisasi_tanam_ha', true, '10.5', 'Hektare'],
                ['keterangan', false, '', self::T],
            ],
        ],
        'hasil-panen' => [
            'judul' => 'Hasil Panen',
            'kolom' => [
                ['kelompok_tani', true, 'POKTAN MEKAR JAYA', 'Nama poktan terdaftar'],
                ['komoditas', true, 'JAGUNG', 'Nama komoditas terdaftar'],
                ['periode_tanam', true, '2026-02', 'Penanaman yang dipanen (YYYY-MM)'],
                ['periode_panen', true, '2026-06', 'Format YYYY-MM'],
                ['realisasi_panen_ha', true, '9', 'Hektare dipanen'],
                ['puso_ha', true, '0.5', 'Hektare gagal panen (isi 0 bila tidak ada)'],
                ['produktivitas', false, '5.2', 'Sesuai satuan baku komoditas per hektare'],
                ['produksi', true, '46.8', 'Total produksi sesuai satuan baku komoditas'],
                ['harga_jual', false, '', 'Rupiah per satuan; opsional'],
                ['keterangan', false, '', self::T],
            ],
        ],
        'infrastruktur' => [
            'judul' => 'Aset Infrastruktur',
            'kolom' => [
                ['satuan_permukiman', true, 'SP Kapitan Meo', 'SP tempat aset berpangkal'],
                ['nama_aset', true, 'Jalan Poros SP', self::T],
                ['jenis', true, 'Jalan', 'Nilai baku jenis infrastruktur', ['dp']],
                ['kondisi', true, 'Baik', 'Nilai baku kondisi', ['dp']],
                ['tahun_perolehan', false, '2018', 'Tahun (YYYY)'],
                ['sumber_dana', false, 'APBN', 'Nilai baku', ['dp']],
                ['kapasitas', false, '2,5 km', self::T],
                ['lintang', false, '-9.512', 'Desimal derajat'],
                ['bujur', false, '124.912', 'Desimal derajat'],
                ['keterangan', false, '', self::T],
            ],
            'daftarPilihan' => ['jenis' => 'JenisInfrastruktur', 'kondisi' => 'Kondisi', 'sumber_dana' => 'SumberDana'],
        ],
        'inventaris-sp' => [
            'judul' => 'Inventaris SP',
            'kolom' => [
                ['satuan_permukiman', true, 'SP Kapitan Meo', 'Nama SP'],
                ['nama_barang', true, 'Komputer Kantor UPT', self::T],
                ['jumlah', true, '2', 'Angka'],
                ['satuan', false, 'unit', self::T],
                ['status_penyerahan', true, 'Sudah Diserahkan', 'Nilai baku', ['dp']],
                ['kondisi', false, 'Baik', 'Nilai baku', ['dp']],
                ['jenis_inventaris', true, 'Perabotan', 'Nilai baku', ['dp']],
                ['tahun_perolehan', false, '2020', 'Tahun (YYYY)'],
                ['sumber_dana', false, 'APBN', 'Nilai baku', ['dp']],
                ['keterangan', false, '', self::T],
            ],
            'daftarPilihan' => [
                'status_penyerahan' => 'StatusPenyerahan', 'kondisi' => 'Kondisi',
                'jenis_inventaris' => 'JenisInventaris', 'sumber_dana' => 'SumberDana',
            ],
        ],
        'fasilitas-sp' => [
            'judul' => 'Fasilitas SP',
            'kolom' => [
                ['satuan_permukiman', true, 'SP Kapitan Meo', 'Nama SP tempat fasilitas berdiri'],
                ['jenis_fasilitas', true, 'Pendidikan Dasar', 'Nilai baku', ['enum:jenis_fasilitas']],
                ['nama_fasilitas', true, 'SD Negeri Kapitan Meo', self::T],
                ['jumlah', true, '1', 'Angka'],
                ['status_penyerahan', true, 'Sudah Diserahkan', 'Nilai baku', ['dp']],
                ['kondisi', false, 'Baik', 'Nilai baku', ['dp']],
                ['tahun_perolehan', false, '2017', 'Tahun (YYYY)'],
                ['sumber_dana', false, 'APBN', 'Nilai baku', ['dp']],
                ['lintang', false, '-9.512', 'Desimal derajat'],
                ['bujur', false, '124.912', 'Desimal derajat'],
                ['keterangan', false, '', self::T],
            ],
            'daftarPilihan' => [
                'status_penyerahan' => 'StatusPenyerahan', 'kondisi' => 'Kondisi', 'sumber_dana' => 'SumberDana',
            ],
        ],
        'satuan' => [
            'judul' => 'Data Master Satuan',
            'kolom' => [
                ['nama', true, 'Ton', 'Nama satuan, unik'],
                ['simbol', true, 't', 'Simbol singkat'],
                ['faktor_ke_ton', false, '1', 'Angka pengali ke ton; kosongkan untuk satuan non-berat'],
            ],
        ],
        'wilayah' => [
            'judul' => 'Wilayah Administratif',
            'kolom' => [
                ['tingkat', true, 'desa', 'provinsi / kabupaten / kecamatan / desa'],
                ['nama', true, 'Kapitan Meo', 'Nama wilayah'],
                ['induk', true, 'Laen Manen', 'Nama wilayah setingkat di atas (kosong untuk provinsi)'],
                ['kode', false, '53.21.09.2001', 'Kode BPS bila ada'],
            ],
        ],
    ];
}
