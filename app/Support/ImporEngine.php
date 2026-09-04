<?php

namespace App\Support;

use App\Enums\Agama;
use App\Enums\JenisDaftarPilihan;
use App\Enums\JenisFasilitas;
use App\Enums\JenisKelamin;
use App\Enums\PendidikanTerakhir;
use App\Enums\StatusTinggal;
use App\Models\Alsintan;
use App\Models\Desa;
use App\Models\FasilitasSp;
use App\Models\Infrastruktur;
use App\Models\InventarisSp;
use App\Models\Kabupaten;
use App\Models\Kecamatan;
use App\Models\Komoditas;
use App\Models\Provinsi;
use App\Models\Satuan;
use App\Models\SatuanPermukiman;
use App\Models\Transmigran;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Mesin impor CSV luring (Task 10.4, 1/2 -- 8 entitas berdiri sendiri).
 *
 * Dua tahap dikerjakan dalam SATU permintaan (tidak ada langkah pratinjau
 * terpisah): tiap baris divalidasi, baris sah langsung tersimpan, baris
 * bermasalah dilewati dan dicatat nomor barisnya -- sesuai janji modal
 * (`components/sim/modal-impor.blade.php`): "Baris bermasalah dilewati,
 * sisanya tetap disimpan."
 *
 * Susunan kolom tiap entitas SATU SUMBER dengan template unduhan
 * (`App\Support\SkemaImpor`), tetapi pemetaan baris -> model TETAP ditulis
 * per entitas (bukan digeneralisasi lewat metadata) sebab tiap entitas punya
 * aturan bisnisnya sendiri -- sama seperti tiap `*Controller::validasi()`
 * berdiri sendiri walau semuanya memakai `Illuminate\Support\Facades\Validator`.
 *
 * DELAPAN entitas berdiri sendiri (tak menaut ke entitas lain yang mungkin
 * belum ada di baris yang sama): satuan, wilayah, komoditas, transmigran,
 * infrastruktur, inventaris-sp, fasilitas-sp, alsintan. Enam entitas berantai
 * (rumah, lahan, poktan, saprotan, penanaman, hasil-panen) BELUM dikerjakan --
 * modal impornya tetap menampilkan spanduk "Fitur belum aktif" (Task 10.4 2/2,
 * menyusul).
 *
 * Kolom relasi diisi petugas dengan NAMA (bukan id) dan dicari ke basis data
 * yang SUDAH ADA di sini -- BUKAN ke baris lain dalam berkas yang sama.
 * Karena itu urutan pengerjaan petugas penting: SP dan data induk lain harus
 * sudah tercatat (manual atau impor) sebelum baris yang merujuknya diimpor.
 *
 * Uppercase otomatis (`App\Http\Middleware\UppercaseInput`) TIDAK berlaku di
 * sini -- middleware itu membaca `$request` input, sedangkan nilai di sini
 * berasal dari isi berkas yang diparsing manual. Data tersimpan APA ADANYA
 * sesuai isian petugas; keseragaman huruf besar pada jalur impor menyusul.
 */
class ImporEngine
{
    /**
     * @return list<string>
     */
    public static function entitasAktif(): array
    {
        return array_keys(self::MODUL);
    }

    public static function aktif(string $entitas): bool
    {
        return array_key_exists($entitas, self::MODUL);
    }

    /**
     * Nama modul RBAC untuk pemeriksaan kewenangan `tambah` dinamis
     * (`ImporController`, pola sama dengan `DokumenController`).
     */
    public static function modul(string $entitas): ?string
    {
        return self::MODUL[$entitas] ?? null;
    }

    /**
     * @var array<string, string>
     */
    private const MODUL = [
        'satuan' => 'satuan',
        'wilayah' => 'wilayah',
        'komoditas' => 'komoditas',
        'transmigran' => 'transmigran',
        'infrastruktur' => 'infrastruktur',
        'inventaris-sp' => 'inventaris_sp',
        'fasilitas-sp' => 'fasilitas_sp',
        'alsintan' => 'alsintan',
    ];

    /**
     * Memproses satu berkas CSV yang sudah tersimpan sementara di disk.
     *
     * @return array{disimpan: int, gagal: list<array{baris: int, pesan: string}>}
     */
    public static function proses(string $entitas, string $pathBerkas): array
    {
        $keluar = fopen($pathBerkas, 'rb');
        abort_unless($keluar !== false, 422, 'Berkas tidak dapat dibaca.');

        $disimpan = 0;
        $gagal = [];
        $posisiKolom = null;
        $nomorBaris = 0;

        // `escape: ''` mematikan perilaku escape non-standar PHP (usang sejak
        // 8.4), sejalan dengan penulis template (`TemplateImporController`).
        while (($sel = fgetcsv($keluar, 0, ',', '"', '')) !== false) {
            $nomorBaris++;

            // BOM UTF-8 pada sel pertama baris pertama (ditulis TemplateImporController).
            if ($nomorBaris === 1 && isset($sel[0])) {
                $sel[0] = self::lucutiBom($sel[0]);
            }

            // Baris petunjuk (#...) dan baris kosong dilewati, tak dihitung.
            if (self::barisKosong($sel) || str_starts_with(trim((string) ($sel[0] ?? '')), '#')) {
                continue;
            }

            // Baris data pertama yang bukan komentar = judul kolom.
            if ($posisiKolom === null) {
                $posisiKolom = array_flip(array_map(fn ($k) => trim((string) $k), $sel));

                continue;
            }

            $baris = self::petakan($sel, $posisiKolom);

            $pesan = match ($entitas) {
                'satuan' => self::barisSatuan($baris),
                'wilayah' => self::barisWilayah($baris),
                'komoditas' => self::barisKomoditas($baris),
                'transmigran' => self::barisTransmigran($baris),
                'infrastruktur' => self::barisInfrastruktur($baris),
                'inventaris-sp' => self::barisInventarisSp($baris),
                'fasilitas-sp' => self::barisFasilitasSp($baris),
                'alsintan' => self::barisAlsintan($baris),
                default => 'Entitas tidak dikenal.',
            };

            if ($pesan === null) {
                $disimpan++;
            } else {
                $gagal[] = ['baris' => $nomorBaris, 'pesan' => $pesan];
            }
        }

        fclose($keluar);

        return ['disimpan' => $disimpan, 'gagal' => $gagal];
    }

    // ------------------------------------------------------------------
    // Pemetaan baris -> model, satu metode per entitas. Mengembalikan NULL
    // bila tersimpan, atau pesan galat (Indonesia, siap tampil) bila gagal.
    // ------------------------------------------------------------------

    private static function barisSatuan(array $b): ?string
    {
        $data = [
            'nama' => self::teks($b, 'nama'),
            'simbol' => self::teks($b, 'simbol'),
            'faktor_ke_ton' => self::angka($b, 'faktor_ke_ton'),
        ];

        $v = Validator::make($data, [
            'nama' => ['required', 'string', 'max:50', Rule::unique('satuan', 'nama')],
            'simbol' => ['required', 'string', 'max:10'],
            'faktor_ke_ton' => ['nullable', 'numeric', 'gt:0', 'max:1000000'],
        ], self::PESAN_UMUM, ['nama' => 'nama', 'simbol' => 'simbol', 'faktor_ke_ton' => 'faktor_ke_ton']);

        if ($v->fails()) {
            return $v->errors()->first();
        }

        Satuan::create($v->validated());

        return null;
    }

    private const TINGKAT_WILAYAH = [
        'provinsi' => ['kelas' => Provinsi::class, 'induk' => null, 'kolomInduk' => null, 'unikGlobal' => true],
        'kabupaten' => ['kelas' => Kabupaten::class, 'induk' => Provinsi::class, 'kolomInduk' => 'provinsi_id', 'unikGlobal' => false],
        'kecamatan' => ['kelas' => Kecamatan::class, 'induk' => Kabupaten::class, 'kolomInduk' => 'kabupaten_id', 'unikGlobal' => false],
        'desa' => ['kelas' => Desa::class, 'induk' => Kecamatan::class, 'kolomInduk' => 'kecamatan_id', 'unikGlobal' => false],
    ];

    private static function barisWilayah(array $b): ?string
    {
        $tingkat = Str::lower(self::teks($b, 'tingkat') ?? '');
        $peta = self::TINGKAT_WILAYAH[$tingkat] ?? null;

        if ($peta === null) {
            return "Kolom tingkat wajib salah satu: provinsi, kabupaten, kecamatan, desa (diisi \"{$tingkat}\").";
        }

        $atribut = ['nama' => self::teks($b, 'nama'), 'kode' => self::teks($b, 'kode')];

        $aturan = ['nama' => ['required', 'string', 'max:100'], 'kode' => ['nullable', 'string', 'max:10']];
        if ($peta['unikGlobal']) {
            $aturan['nama'][] = Rule::unique('provinsi', 'nama');
        }

        if ($peta['induk'] !== null) {
            $namaInduk = self::teks($b, 'induk');

            if ($namaInduk === null) {
                return 'Kolom induk wajib diisi untuk tingkat '.$tingkat.'.';
            }

            $idInduk = self::cariIdTunggal($peta['induk'], 'nama', $namaInduk);
            if (is_string($idInduk)) {
                return $idInduk;
            }

            $atribut[$peta['kolomInduk']] = $idInduk;
        }

        $v = Validator::make($atribut, $aturan, self::PESAN_UMUM, ['nama' => 'nama', 'kode' => 'kode']);
        if ($v->fails()) {
            return $v->errors()->first();
        }

        $simpan = $v->validated();
        if ($peta['induk'] !== null) {
            $simpan[$peta['kolomInduk']] = $atribut[$peta['kolomInduk']];
        }

        $peta['kelas']::create($simpan);

        return null;
    }

    private static function barisKomoditas(array $b): ?string
    {
        $namaSatuan = self::teks($b, 'satuan_baku');
        $satuanId = $namaSatuan === null ? null : self::cariIdTunggal(Satuan::class, 'nama', $namaSatuan);
        if (is_string($satuanId)) {
            return $satuanId;
        }

        $data = [
            'nama' => self::teks($b, 'nama_komoditas'),
            'tipe' => self::teks($b, 'jenis'),
            'satuan_id' => $satuanId,
            'is_unggulan' => self::boolean($b, 'unggulan'),
            'deskripsi' => self::teks($b, 'deskripsi'),
        ];

        $v = Validator::make($data, [
            'nama' => ['required', 'string', 'max:100', Rule::unique('komoditas', 'nama')],
            'tipe' => ValidationRules::daftarPilihan(JenisDaftarPilihan::TipeKomoditas, wajib: true),
            'satuan_id' => ['required', 'integer', Rule::exists('satuan', 'id_satuan')],
            'is_unggulan' => ['nullable', 'boolean'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
        ], self::PESAN_UMUM + [
            'satuan_id.required' => 'Kolom satuan_baku wajib diisi dan namanya harus sudah terdaftar.',
        ], [
            'nama' => 'nama_komoditas', 'tipe' => 'jenis', 'satuan_id' => 'satuan_baku',
            'is_unggulan' => 'unggulan', 'deskripsi' => 'deskripsi',
        ]);

        if ($v->fails()) {
            return $v->errors()->first();
        }

        Komoditas::create($v->validated());

        return null;
    }

    private static function barisTransmigran(array $b): ?string
    {
        $namaSp = self::teks($b, 'satuan_permukiman');
        $spId = $namaSp === null ? null : self::cariIdTunggal(SatuanPermukiman::class, 'nama', $namaSp);
        if (is_string($spId)) {
            return $spId;
        }

        $namaKabupaten = self::teks($b, 'daerah_asal_kabupaten');
        $kabupatenId = null;
        if ($namaKabupaten !== null) {
            $kabupatenId = self::cariIdTunggal(Kabupaten::class, 'nama', $namaKabupaten);
            if (is_string($kabupatenId)) {
                return $kabupatenId;
            }
        }

        $data = [
            'nik' => self::teks($b, 'nik'),
            'nama_kepala_keluarga' => self::teks($b, 'nama_lengkap'),
            'no_kk' => self::teks($b, 'no_kk'),
            'satuan_permukiman_id' => $spId,
            'jenis_kelamin' => self::teks($b, 'jenis_kelamin'),
            'agama' => self::teks($b, 'agama'),
            'tempat_lahir' => self::teks($b, 'tempat_lahir'),
            'tanggal_lahir' => self::teks($b, 'tanggal_lahir'),
            'pendidikan_terakhir' => self::teks($b, 'pendidikan_terakhir'),
            'pekerjaan_kepala_keluarga' => self::teks($b, 'pekerjaan'),
            'pendapatan_per_bulan' => self::angka($b, 'pendapatan_per_bulan'),
            'daerah_asal_kabupaten_id' => $kabupatenId,
            'tahun_kedatangan' => self::angka($b, 'tahun_kedatangan'),
            'status_tinggal' => self::teks($b, 'status_tinggal'),
            'telepon' => self::teks($b, 'telepon'),
            'keterangan' => self::teks($b, 'keterangan'),
        ];

        $v = Validator::make($data, [
            'nik' => ['required', 'digits:16', Rule::unique('transmigran', 'nik')],
            'nama_kepala_keluarga' => ['required', 'string', 'min:3', 'max:255'],
            'no_kk' => ['required', 'digits:16', Rule::unique('transmigran', 'no_kk')],
            'satuan_permukiman_id' => ['required', 'integer', Rule::exists('satuan_permukiman', 'id_satuan_permukiman')],
            'jenis_kelamin' => ['nullable', Rule::enum(JenisKelamin::class)],
            'agama' => ['nullable', Rule::enum(Agama::class)],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['nullable', 'date', 'before_or_equal:today'],
            'pendidikan_terakhir' => ['nullable', Rule::enum(PendidikanTerakhir::class)],
            'pekerjaan_kepala_keluarga' => ['required', 'string', 'max:100'],
            'pendapatan_per_bulan' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'daerah_asal_kabupaten_id' => ['nullable', 'integer'],
            'tahun_kedatangan' => ['required', 'integer', 'min:1900', 'max:'.date('Y')],
            'status_tinggal' => ['required', Rule::enum(StatusTinggal::class)],
            'telepon' => ['nullable', 'string', 'regex:/^(08|\+62)[0-9]{8,13}$/'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ], self::PESAN_UMUM + [
            'satuan_permukiman_id.required' => 'Kolom satuan_permukiman wajib diisi dan namanya harus sudah terdaftar.',
        ], [
            'nik' => 'nik', 'nama_kepala_keluarga' => 'nama_lengkap', 'no_kk' => 'no_kk',
            'satuan_permukiman_id' => 'satuan_permukiman', 'jenis_kelamin' => 'jenis_kelamin',
            'agama' => 'agama', 'tempat_lahir' => 'tempat_lahir', 'tanggal_lahir' => 'tanggal_lahir',
            'pendidikan_terakhir' => 'pendidikan_terakhir', 'pekerjaan_kepala_keluarga' => 'pekerjaan',
            'pendapatan_per_bulan' => 'pendapatan_per_bulan',
            'daerah_asal_kabupaten_id' => 'daerah_asal_kabupaten', 'tahun_kedatangan' => 'tahun_kedatangan',
            'status_tinggal' => 'status_tinggal', 'telepon' => 'telepon', 'keterangan' => 'keterangan',
        ]);

        if ($v->fails()) {
            return $v->errors()->first();
        }

        Transmigran::create($v->validated() + ['uuid' => (string) Str::uuid()]);

        return null;
    }

    private static function barisInfrastruktur(array $b): ?string
    {
        $spId = self::wajibSp($b);
        if (is_string($spId)) {
            return $spId;
        }

        $data = [
            'satuan_permukiman_id' => $spId,
            'nama' => self::teks($b, 'nama_aset'),
            'jenis' => self::teks($b, 'jenis'),
            'tahun_perolehan' => self::angka($b, 'tahun_perolehan'),
            'sumber_dana' => self::teks($b, 'sumber_dana'),
            'kondisi' => self::teks($b, 'kondisi'),
            'kapasitas' => self::teks($b, 'kapasitas'),
            'lintang' => self::angka($b, 'lintang'),
            'bujur' => self::angka($b, 'bujur'),
            'keterangan' => self::teks($b, 'keterangan'),
        ];

        $v = Validator::make($data, [
            'satuan_permukiman_id' => ['required', 'integer'],
            'nama' => ['required', 'string', 'max:150'],
            'jenis' => ValidationRules::daftarPilihan(JenisDaftarPilihan::JenisInfrastruktur, wajib: true),
            'tahun_perolehan' => ['nullable', 'integer', 'min:1900', 'max:'.date('Y')],
            'sumber_dana' => ValidationRules::daftarPilihan(JenisDaftarPilihan::SumberDana),
            'kondisi' => ValidationRules::daftarPilihan(JenisDaftarPilihan::Kondisi, wajib: true),
            'kapasitas' => ['nullable', 'string', 'max:100'],
            'lintang' => ['nullable', 'numeric', 'between:-90,90'],
            'bujur' => ['nullable', 'numeric', 'between:-180,180'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ], self::PESAN_UMUM, [
            'satuan_permukiman_id' => 'satuan_permukiman', 'nama' => 'nama_aset', 'jenis' => 'jenis',
            'tahun_perolehan' => 'tahun_perolehan', 'sumber_dana' => 'sumber_dana', 'kondisi' => 'kondisi',
            'kapasitas' => 'kapasitas', 'lintang' => 'lintang', 'bujur' => 'bujur', 'keterangan' => 'keterangan',
        ]);

        if ($v->fails()) {
            return $v->errors()->first();
        }

        $infra = Infrastruktur::create($v->validated());
        $infra->cakupan()->sync([$spId]);

        return null;
    }

    private static function barisInventarisSp(array $b): ?string
    {
        $spId = self::wajibSp($b);
        if (is_string($spId)) {
            return $spId;
        }

        $data = [
            'satuan_permukiman_id' => $spId,
            'nama_barang' => self::teks($b, 'nama_barang'),
            'jumlah' => self::angka($b, 'jumlah'),
            'satuan_barang' => self::teks($b, 'satuan'),
            'tahun_perolehan' => self::angka($b, 'tahun_perolehan'),
            'jenis_inventaris' => self::teks($b, 'jenis_inventaris'),
            'sumber_dana' => self::teks($b, 'sumber_dana'),
            'status_penyerahan' => self::teks($b, 'status_penyerahan'),
            'kondisi' => self::teks($b, 'kondisi'),
            'keterangan' => self::teks($b, 'keterangan'),
        ];

        $v = Validator::make($data, [
            'satuan_permukiman_id' => ['required', 'integer'],
            'nama_barang' => ['required', 'string', 'max:150'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:1000000'],
            'satuan_barang' => ['nullable', 'string', 'max:20'],
            'tahun_perolehan' => ['nullable', 'integer', 'min:1900', 'max:'.date('Y')],
            // NOT NULL di skema (`inventaris_sp.jenis_inventaris`) walau validasi
            // form manual menandainya nullable -- celah pra-ada di luar lingkup
            // Task 10.4; di sini diwajibkan supaya galatnya rapi, bukan SQL mentah.
            'jenis_inventaris' => ValidationRules::daftarPilihan(JenisDaftarPilihan::JenisInventaris, wajib: true),
            'sumber_dana' => ValidationRules::daftarPilihan(JenisDaftarPilihan::SumberDana),
            'status_penyerahan' => ValidationRules::daftarPilihan(JenisDaftarPilihan::StatusPenyerahan, wajib: true),
            'kondisi' => ValidationRules::daftarPilihan(JenisDaftarPilihan::Kondisi),
            'keterangan' => ['nullable', 'string', 'max:500'],
        ], self::PESAN_UMUM, [
            'satuan_permukiman_id' => 'satuan_permukiman', 'nama_barang' => 'nama_barang',
            'jumlah' => 'jumlah', 'satuan_barang' => 'satuan', 'tahun_perolehan' => 'tahun_perolehan',
            'jenis_inventaris' => 'jenis_inventaris', 'sumber_dana' => 'sumber_dana',
            'status_penyerahan' => 'status_penyerahan', 'kondisi' => 'kondisi', 'keterangan' => 'keterangan',
        ]);

        if ($v->fails()) {
            return $v->errors()->first();
        }

        InventarisSp::create($v->validated());

        return null;
    }

    private static function barisFasilitasSp(array $b): ?string
    {
        $spId = self::wajibSp($b);
        if (is_string($spId)) {
            return $spId;
        }

        $data = [
            'satuan_permukiman_id' => $spId,
            'jenis_fasilitas' => self::teks($b, 'jenis_fasilitas'),
            'nama_fasilitas' => self::teks($b, 'nama_fasilitas'),
            'jumlah' => self::angka($b, 'jumlah'),
            'tahun_perolehan' => self::angka($b, 'tahun_perolehan'),
            'sumber_dana' => self::teks($b, 'sumber_dana'),
            'status_penyerahan' => self::teks($b, 'status_penyerahan'),
            'kondisi' => self::teks($b, 'kondisi'),
            'lintang' => self::angka($b, 'lintang'),
            'bujur' => self::angka($b, 'bujur'),
            'keterangan' => self::teks($b, 'keterangan'),
        ];

        $v = Validator::make($data, [
            'satuan_permukiman_id' => ['required', 'integer'],
            'jenis_fasilitas' => ['required', Rule::enum(JenisFasilitas::class)],
            'nama_fasilitas' => ['required', 'string', 'max:150'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:100000'],
            'tahun_perolehan' => ['nullable', 'integer', 'min:1900', 'max:'.date('Y')],
            'sumber_dana' => ValidationRules::daftarPilihan(JenisDaftarPilihan::SumberDana),
            'status_penyerahan' => ValidationRules::daftarPilihan(JenisDaftarPilihan::StatusPenyerahan, wajib: true),
            'kondisi' => ValidationRules::daftarPilihan(JenisDaftarPilihan::Kondisi),
            'lintang' => ['nullable', 'numeric', 'between:-90,90'],
            'bujur' => ['nullable', 'numeric', 'between:-180,180'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ], self::PESAN_UMUM, [
            'satuan_permukiman_id' => 'satuan_permukiman', 'jenis_fasilitas' => 'jenis_fasilitas',
            'nama_fasilitas' => 'nama_fasilitas', 'jumlah' => 'jumlah', 'tahun_perolehan' => 'tahun_perolehan',
            'sumber_dana' => 'sumber_dana', 'status_penyerahan' => 'status_penyerahan', 'kondisi' => 'kondisi',
            'lintang' => 'lintang', 'bujur' => 'bujur', 'keterangan' => 'keterangan',
        ]);

        if ($v->fails()) {
            return $v->errors()->first();
        }

        $fasilitas = FasilitasSp::create($v->validated());
        $fasilitas->cakupan()->sync([$spId]);

        return null;
    }

    private static function barisAlsintan(array $b): ?string
    {
        $data = [
            'jenis_alsintan' => self::teks($b, 'jenis_alsintan'),
            'nama_alat' => self::teks($b, 'nama_alat'),
            'jumlah_total' => self::angka($b, 'jumlah_total'),
            'tahun_pengadaan' => self::angka($b, 'tahun_pengadaan'),
            'sumber_dana' => self::teks($b, 'sumber_dana'),
            'keterangan' => self::teks($b, 'keterangan'),
        ];

        $v = Validator::make($data, [
            'jenis_alsintan' => ValidationRules::daftarPilihan(JenisDaftarPilihan::JenisAlsintan, wajib: true),
            'nama_alat' => ['required', 'string', 'max:255'],
            'jumlah_total' => ['required', 'integer', 'min:1', 'max:999999'],
            'tahun_pengadaan' => ['nullable', 'integer', 'min:1900', 'max:'.date('Y')],
            'sumber_dana' => ValidationRules::daftarPilihan(JenisDaftarPilihan::SumberDana),
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ], self::PESAN_UMUM, [
            'jenis_alsintan' => 'jenis_alsintan', 'nama_alat' => 'nama_alat',
            'jumlah_total' => 'jumlah_total', 'tahun_pengadaan' => 'tahun_pengadaan',
            'sumber_dana' => 'sumber_dana', 'keterangan' => 'keterangan',
        ]);

        if ($v->fails()) {
            return $v->errors()->first();
        }

        Alsintan::create($v->validated());

        return null;
    }

    // ------------------------------------------------------------------
    // Bahan bersama
    // ------------------------------------------------------------------

    /**
     * Pesan galat generik berbahasa Indonesia, satu per NAMA ATURAN (bukan per
     * kolom): Laravel mencari `{kolom}.{aturan}` lebih dulu, baru jatuh ke
     * `{aturan}` di sini bila tak ada pesan spesifik. `:attribute` diisi dari
     * larik label per metode (lihat pemanggilan `Validator::make`).
     */
    private const PESAN_UMUM = [
        'required' => 'Kolom :attribute wajib diisi.',
        'string' => 'Kolom :attribute harus berupa teks.',
        'integer' => 'Kolom :attribute harus berupa bilangan bulat.',
        'numeric' => 'Kolom :attribute harus berupa angka.',
        'max' => 'Kolom :attribute melebihi batas panjang/nilai yang diizinkan.',
        'min' => 'Kolom :attribute kurang dari batas minimum.',
        'digits' => 'Kolom :attribute harus tepat :digits digit angka.',
        'unique' => 'Isian :attribute ini sudah terdaftar.',
        'exists' => 'Nilai kolom :attribute tidak ditemukan pada data master.',
        'date' => 'Kolom :attribute bukan tanggal yang sah.',
        'before_or_equal' => 'Kolom :attribute tidak boleh melewati hari ini.',
        'regex' => 'Format kolom :attribute tidak sesuai.',
        'enum' => 'Nilai kolom :attribute tidak sah.',
        'between' => 'Kolom :attribute harus di antara :min dan :max.',
        'in' => 'Nilai kolom :attribute tidak sah.',
        'gt' => 'Kolom :attribute harus lebih besar dari :value.',
        'boolean' => 'Kolom :attribute hanya menerima ya/tidak.',
    ];

    private static function wajibSp(array $b): int|string
    {
        $nama = self::teks($b, 'satuan_permukiman');

        if ($nama === null) {
            return 'Kolom satuan_permukiman wajib diisi.';
        }

        return self::cariIdTunggal(SatuanPermukiman::class, 'nama', $nama);
    }

    /**
     * Mencari satu baris menurut nama persis (peka huruf besar-kecil MySQL
     * `utf8mb4_unicode_ci` = tidak peka, cukup untuk isian petugas). Tak
     * ketemu atau lebih dari satu -> pesan galat siap tampil.
     *
     * @param  class-string  $model
     */
    private static function cariIdTunggal(string $model, string $kolom, string $nilai): int|string
    {
        $kunci = (new $model)->getKeyName();
        $cocok = $model::query()->where($kolom, trim($nilai))->limit(2)->pluck($kunci);

        if ($cocok->isEmpty()) {
            return "\"{$nilai}\" tidak ditemukan. Pastikan sudah terdaftar dan namanya dieja persis sama.";
        }

        if ($cocok->count() > 1) {
            return "\"{$nilai}\" ditemukan lebih dari satu baris, tidak dapat ditentukan.";
        }

        return (int) $cocok->first();
    }

    private static function petakan(array $sel, array $posisiKolom): array
    {
        $baris = [];
        foreach ($posisiKolom as $kolom => $posisi) {
            $baris[$kolom] = $sel[$posisi] ?? null;
        }

        return $baris;
    }

    private static function teks(array $b, string $kolom): ?string
    {
        $nilai = trim((string) ($b[$kolom] ?? ''));

        return $nilai === '' ? null : $nilai;
    }

    /**
     * Bila isiannya bukan angka, nilai MENTAH dikembalikan apa adanya
     * (bukan null) supaya aturan `integer`/`numeric` menolaknya dengan pesan
     * yang jelas -- bukan diam-diam dianggap kosong.
     */
    private static function angka(array $b, string $kolom): int|float|string|null
    {
        $nilai = self::teks($b, $kolom);
        if ($nilai === null || ! is_numeric($nilai)) {
            return $nilai;
        }

        return str_contains($nilai, '.') ? (float) $nilai : (int) $nilai;
    }

    private static function boolean(array $b, string $kolom): bool
    {
        $nilai = Str::lower(self::teks($b, $kolom) ?? '');

        return in_array($nilai, ['ya', 'true', '1', 'yes'], true);
    }

    private static function barisKosong(array $sel): bool
    {
        foreach ($sel as $s) {
            if (trim((string) $s) !== '') {
                return false;
            }
        }

        return true;
    }

    private static function lucutiBom(string $s): string
    {
        return str_starts_with($s, "\xEF\xBB\xBF") ? substr($s, 3) : $s;
    }
}
