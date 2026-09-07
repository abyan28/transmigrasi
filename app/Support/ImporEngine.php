<?php

namespace App\Support;

use App\Enums\Agama;
use App\Enums\AsalWakilPoktan;
use App\Enums\JenisDaftarPilihan;
use App\Enums\JenisKelamin;
use App\Enums\PendidikanTerakhir;
use App\Enums\StatusKeaktifanAnggota;
use App\Enums\StatusTinggal;
use App\Models\Alsintan;
use App\Models\AnggotaKeluarga;
use App\Models\DaftarPilihan;
use App\Models\Desa;
use App\Models\FasilitasSp;
use App\Models\HasilPanen;
use App\Models\Infrastruktur;
use App\Models\InventarisSp;
use App\Models\Kabupaten;
use App\Models\Kecamatan;
use App\Models\Komoditas;
use App\Models\Lahan;
use App\Models\Penanaman;
use App\Models\Poktan;
use App\Models\Provinsi;
use App\Models\Rumah;
use App\Models\Saprotan;
use App\Models\SaprotanDistribusi;
use App\Models\Satuan;
use App\Models\SatuanPermukiman;
use App\Models\Scopes\CakupanDataSp;
use App\Models\Transmigran;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;
use ZipArchive;

/**
 * Mesin impor XLSX/CSV luring untuk entitas mandiri dan berantai.
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
 * Poktan dan Saprotan diproses per kelompok identitas agar induk beserta
 * seluruh anaknya atomik. Entitas lain diproses atomik per baris.
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
        'rumah' => 'rumah',
        'lahan' => 'lahan',
        'poktan' => 'poktan',
        'saprotan' => 'saprotan',
        'penanaman' => 'penanaman',
        'hasil-panen' => 'hasil_panen',
    ];

    private const MAKS_BARIS_DATA = 1000;

    private const MAKS_GALAT_RINCI = 100;

    private const MAKS_ENTRI_ZIP = 500;

    private const MAKS_UKURAN_ZIP_TERBUKA = 50 * 1024 * 1024;

    private const MAKS_RASIO_ZIP = 200;

    /**
     * @return array{diproses: int, dibuat: int, dilewati: int, jumlah_gagal: int, gagal: list<array{baris: int|string, pesan: string}>, galat_dibatasi: bool}
     */
    public static function proses(string $entitas, string $pathBerkas, string $format): array
    {
        if (! self::aktif($entitas) || ! in_array($format, ['csv', 'xlsx'], true)) {
            throw new InvalidArgumentException('Entitas atau format impor tidak didukung.');
        }

        $barisBerkas = $format === 'xlsx'
            ? self::bacaXlsx($entitas, $pathBerkas)
            : self::bacaCsv($pathBerkas);
        [$judul, $barisData] = self::validasiDanPetakan($entitas, $barisBerkas);

        if (in_array($entitas, ['poktan', 'saprotan'], true)) {
            return self::prosesKelompok($entitas, $judul, $barisData);
        }

        $dibuat = 0;
        $dilewati = 0;
        $jumlahGagal = 0;
        $gagal = [];

        foreach ($barisData as [$nomorBaris, $sel]) {
            $baris = self::petakan($sel, $judul);

            try {
                foreach (SkemaImpor::kolomTanggal($entitas) as $kolomTanggal) {
                    $baris[$kolomTanggal] = self::normalisasiTanggal($baris[$kolomTanggal] ?? null, $nomorBaris, $kolomTanggal);
                }

                $hasil = DB::transaction(fn (): array|string|null => match ($entitas) {
                    'satuan' => self::barisSatuan($baris),
                    'wilayah' => self::barisWilayah($baris),
                    'komoditas' => self::barisKomoditas($baris),
                    'transmigran' => self::barisTransmigran($baris),
                    'infrastruktur' => self::barisInfrastruktur($baris),
                    'inventaris-sp' => self::barisInventarisSp($baris),
                    'fasilitas-sp' => self::barisFasilitasSp($baris),
                    'alsintan' => self::barisAlsintan($baris),
                    'rumah' => self::barisRumah($baris),
                    'lahan' => self::barisLahan($baris),
                    'poktan' => self::barisPoktan($baris),
                    'saprotan' => self::barisSaprotan($baris),
                    'penanaman' => self::barisPenanaman($baris),
                    'hasil-panen' => self::barisHasilPanen($baris),
                });
            } catch (ValidationException $e) {
                $hasil = $e->validator->errors()->first();
            } catch (Throwable $e) {
                report($e);
                $hasil = 'Baris gagal disimpan. Periksa data dan cakupan SP Anda.';
            }

            if ($hasil === null || $hasil === ['status' => 'dibuat']) {
                $dibuat++;

                continue;
            }

            if ($hasil === ['status' => 'dilewati']) {
                $dilewati++;

                continue;
            }

            $jumlahGagal++;
            if (count($gagal) < self::MAKS_GALAT_RINCI) {
                $gagal[] = ['baris' => $nomorBaris, 'pesan' => is_string($hasil) ? $hasil : 'Baris gagal disimpan.'];
            }
        }

        return [
            'diproses' => count($barisData),
            'dibuat' => $dibuat,
            'dilewati' => $dilewati,
            'jumlah_gagal' => $jumlahGagal,
            'gagal' => $gagal,
            'galat_dibatasi' => $jumlahGagal > count($gagal),
        ];
    }

    /**
     * @param  array<string, int>  $judul
     * @param  list<array{0:int,1:list<mixed>}>  $barisData
     */
    private static function prosesKelompok(string $entitas, array $judul, array $barisData): array
    {
        $kunci = $entitas === 'poktan' ? 'nama_poktan' : 'kode_saprotan';
        $kelompok = [];

        foreach ($barisData as [$nomor, $sel]) {
            $baris = self::petakan($sel, $judul);
            foreach (SkemaImpor::kolomTanggal($entitas) as $kolomTanggal) {
                $baris[$kolomTanggal] = self::normalisasiTanggal($baris[$kolomTanggal] ?? null, $nomor, $kolomTanggal);
            }
            $kelompok[trim((string) ($baris[$kunci] ?? ''))][] = [$nomor, $baris];
        }

        $dibuat = $dilewati = $jumlahGagal = $jumlahKelompokGagal = 0;
        $gagal = [];

        foreach ($kelompok as $barisKelompok) {
            $nomor = array_column($barisKelompok, 0);
            try {
                $hasil = DB::transaction(fn (): array|string => $entitas === 'poktan'
                    ? self::kelompokPoktan($barisKelompok)
                    : self::kelompokSaprotan($barisKelompok));
            } catch (ValidationException $e) {
                $hasil = $e->validator->errors()->first();
            } catch (Throwable $e) {
                report($e);
                $hasil = 'Kelompok gagal disimpan. Periksa data dan cakupan SP Anda.';
            }

            if ($hasil === ['status' => 'dibuat']) {
                $dibuat += count($barisKelompok);
            } elseif ($hasil === ['status' => 'dilewati']) {
                $dilewati += count($barisKelompok);
            } else {
                $jumlahGagal += count($barisKelompok);
                $jumlahKelompokGagal++;
                if (count($gagal) < self::MAKS_GALAT_RINCI) {
                    $gagal[] = [
                        'baris' => count($nomor) === 1 ? $nomor[0] : min($nomor).'-'.max($nomor),
                        'pesan' => is_string($hasil) ? $hasil : 'Kelompok gagal disimpan.',
                    ];
                }
            }
        }

        return [
            'diproses' => count($barisData), 'dibuat' => $dibuat, 'dilewati' => $dilewati,
            'jumlah_gagal' => $jumlahGagal, 'gagal' => $gagal,
            'galat_dibatasi' => $jumlahKelompokGagal > count($gagal),
        ];
    }

    /**
     * @return list<array{0:int,1:list<mixed>}>
     */
    private static function bacaCsv(string $pathBerkas): array
    {
        $berkas = fopen($pathBerkas, 'rb');
        if ($berkas === false) {
            throw new InvalidArgumentException('Berkas tidak dapat dibaca.');
        }

        $hasil = [];
        $nomor = 0;

        try {
            while (($sel = fgetcsv($berkas, 0, ',', '"', '')) !== false) {
                $nomor++;
                if ($nomor === 1 && isset($sel[0])) {
                    $sel[0] = self::lucutiBom((string) $sel[0]);
                }
                if (self::barisKosong($sel) || str_starts_with(trim((string) ($sel[0] ?? '')), '#')) {
                    continue;
                }
                foreach ($sel as $nilai) {
                    self::tolakFormulaCsv($nilai);
                }
                $hasil[] = [$nomor, $sel];
            }
        } finally {
            fclose($berkas);
        }

        return $hasil;
    }

    /**
     * @return list<array{0:int,1:list<mixed>}>
     */
    private static function bacaXlsx(string $entitas, string $pathBerkas): array
    {
        self::periksaZipXlsx($pathBerkas);

        try {
            $pembaca = new Xlsx;
            $pembaca->setReadDataOnly(false);
            $pembaca->setReadEmptyCells(false);
            $info = $pembaca->listWorksheetInfo($pathBerkas);
        } catch (Throwable) {
            throw new InvalidArgumentException('Berkas XLSX rusak atau bukan XLSX yang sah.');
        }

        $data = array_values(array_filter($info, fn (array $lembar): bool => $lembar['worksheetName'] === 'Data'));
        if (count($data) !== 1 || ($data[0]['sheetState'] ?? Worksheet::SHEETSTATE_VISIBLE) !== Worksheet::SHEETSTATE_VISIBLE) {
            throw new InvalidArgumentException('XLSX harus memiliki tepat satu sheet Data yang terlihat.');
        }

        foreach ($info as $lembar) {
            $nama = $lembar['worksheetName'];
            $status = $lembar['sheetState'] ?? Worksheet::SHEETSTATE_VISIBLE;
            $aman = $nama === 'Data'
                || (in_array($nama, ['Petunjuk', 'Contoh'], true) && $status === Worksheet::SHEETSTATE_VISIBLE)
                || ($nama === 'Referensi' && in_array($status, [Worksheet::SHEETSTATE_HIDDEN, Worksheet::SHEETSTATE_VERYHIDDEN], true));
            if (! $aman) {
                throw new InvalidArgumentException('XLSX hanya boleh memuat sheet Data, Petunjuk/Contoh, dan Referensi tersembunyi.');
            }
        }

        $maksKolom = count(SkemaImpor::kolom($entitas));
        if ($data[0]['totalRows'] > self::MAKS_BARIS_DATA + 1 || $data[0]['totalColumns'] > $maksKolom) {
            throw new InvalidArgumentException('XLSX melampaui batas 1000 baris data atau jumlah kolom skema.');
        }

        try {
            $pembaca->setLoadSheetsOnly('Data');
            $buku = $pembaca->load($pathBerkas);
            $lembar = $buku->getSheetByName('Data');
            if ($lembar === null) {
                throw new InvalidArgumentException('Sheet Data tidak ditemukan.');
            }

            $hasil = [];
            for ($nomor = 1; $nomor <= $lembar->getHighestDataRow(); $nomor++) {
                $sel = [];
                $kolomTerakhir = Coordinate::columnIndexFromString($lembar->getHighestDataColumn($nomor));
                for ($kolom = 1; $kolom <= $kolomTerakhir; $kolom++) {
                    $cell = $lembar->getCell([$kolom, $nomor]);
                    if ($cell->isFormula()) {
                        throw new InvalidArgumentException("Formula tidak diizinkan pada sheet Data (baris {$nomor}).");
                    }
                    $sel[] = $cell->getValue();
                }
                if (! self::barisKosong($sel)) {
                    $hasil[] = [$nomor, $sel];
                }
            }
            $buku->disconnectWorksheets();

            return $hasil;
        } catch (InvalidArgumentException $e) {
            throw $e;
        } catch (Throwable) {
            throw new InvalidArgumentException('Berkas XLSX rusak atau tidak dapat dibaca.');
        }
    }

    private static function periksaZipXlsx(string $pathBerkas): void
    {
        $zip = new ZipArchive;
        $hasilBuka = $zip->open($pathBerkas);
        if ($hasilBuka !== true) {
            throw new InvalidArgumentException('Berkas XLSX rusak atau jumlah entri ZIP melampaui batas.');
        }
        if ($zip->numFiles < 1 || $zip->numFiles > self::MAKS_ENTRI_ZIP) {
            $zip->close();
            throw new InvalidArgumentException('Berkas XLSX rusak atau jumlah entri ZIP melampaui batas.');
        }

        $ukuranTerbuka = 0;
        $ukuranPadat = 0;

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if ($stat === false) {
                    throw new InvalidArgumentException('Struktur ZIP XLSX tidak sah.');
                }

                $nama = str_replace('\\', '/', strtolower($stat['name']));
                $ukuran = (int) $stat['size'];
                $padat = (int) $stat['comp_size'];
                $ukuranTerbuka += $ukuran;
                $ukuranPadat += $padat;

                if (str_contains($nama, 'vbaproject.bin')
                    || str_starts_with($nama, 'xl/externallinks/')
                    || str_starts_with($nama, 'xl/embeddings/')
                    || ($padat > 0 && $ukuran / $padat > self::MAKS_RASIO_ZIP)) {
                    throw new InvalidArgumentException('XLSX mengandung makro, tautan eksternal, objek tertanam, atau rasio kompresi tidak aman.');
                }

                if (str_ends_with($nama, '.xml') || str_ends_with($nama, '.rels')) {
                    $isi = $zip->getFromIndex($i);
                    if (is_string($isi) && preg_match('/TargetMode\s*=\s*["\']External["\']/i', $isi) === 1) {
                        throw new InvalidArgumentException('Tautan eksternal tidak diizinkan dalam XLSX.');
                    }
                    if (is_string($isi) && preg_match('/macroEnabled|vnd\.ms-office\.vbaProject/i', $isi) === 1) {
                        throw new InvalidArgumentException('Makro tidak diizinkan dalam XLSX.');
                    }
                }
            }

            if ($ukuranTerbuka > self::MAKS_UKURAN_ZIP_TERBUKA
                || ($ukuranPadat > 0 && $ukuranTerbuka / $ukuranPadat > self::MAKS_RASIO_ZIP)) {
                throw new InvalidArgumentException('Ukuran XLSX setelah diekstrak melampaui batas aman.');
            }
        } finally {
            $zip->close();
        }
    }

    /**
     * @param  list<array{0:int,1:list<mixed>}>  $barisBerkas
     * @return array{0:array<string,int>,1:list<array{0:int,1:list<mixed>}>}
     */
    private static function validasiDanPetakan(string $entitas, array $barisBerkas): array
    {
        if ($barisBerkas === []) {
            throw new InvalidArgumentException('Berkas kosong atau tidak memiliki baris judul.');
        }

        [$nomorJudul, $judulMentah] = array_shift($barisBerkas);
        $judul = array_map(fn ($nilai): string => trim((string) $nilai), $judulMentah);
        if (isset($judul[0])) {
            $judul[0] = self::lucutiBom($judul[0]);
        }

        if (count($judul) !== count(array_unique($judul))) {
            throw new InvalidArgumentException("Judul kolom duplikat ditemukan pada baris {$nomorJudul}.");
        }

        $diharapkan = array_column(SkemaImpor::kolom($entitas), 'kolom');
        $hilang = array_values(array_diff($diharapkan, $judul));
        $asing = array_values(array_diff($judul, $diharapkan));
        if (count($judul) !== count($diharapkan) || $hilang !== [] || $asing !== []) {
            $bagian = [];
            if ($hilang !== []) {
                $bagian[] = 'hilang: '.implode(', ', $hilang);
            }
            if ($asing !== []) {
                $bagian[] = 'tidak dikenal: '.implode(', ', $asing);
            }
            throw new InvalidArgumentException('Judul kolom tidak sesuai skema'.($bagian === [] ? '.' : ' ('.implode('; ', $bagian).').'));
        }

        if ($barisBerkas === []) {
            throw new InvalidArgumentException('Berkas hanya berisi judul kolom; tambahkan minimal satu baris data.');
        }
        if (count($barisBerkas) > self::MAKS_BARIS_DATA) {
            throw new InvalidArgumentException('Maksimal 1000 baris data per berkas.');
        }

        foreach ($barisBerkas as $pasangan) {
            if (count($pasangan[1]) > count($diharapkan)) {
                throw new InvalidArgumentException("Baris {$pasangan[0]} memiliki kolom melebihi skema.");
            }
        }

        return [array_flip($judul), $barisBerkas];
    }

    private static function normalisasiTanggal(mixed $nilai, int $baris, string $kolom): mixed
    {
        if ($nilai === null || trim((string) $nilai) === '') {
            return null;
        }

        if (is_int($nilai) || is_float($nilai)) {
            try {
                return Date::excelToDateTimeObject($nilai)->format('Y-m-d');
            } catch (Throwable) {
                return (string) $nilai;
            }
        }

        $teks = trim((string) $nilai);
        foreach (['!Y-m-d', '!d/m/Y', '!d-m-Y'] as $format) {
            $tanggal = DateTimeImmutable::createFromFormat($format, $teks);
            if ($tanggal !== false && $tanggal->format(substr($format, 1)) === $teks) {
                return $tanggal->format('Y-m-d');
            }
        }

        return $teks;
    }

    private static function tolakFormulaCsv(mixed $nilai): void
    {
        $teks = ltrim((string) $nilai);
        if ($teks === '' || is_numeric($teks) || preg_match('/^\+62\d+$/', $teks) === 1) {
            return;
        }
        if (in_array($teks[0], ['=', '+', '-', '@'], true)) {
            throw new InvalidArgumentException('Formula tidak diizinkan dalam berkas CSV.');
        }
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
        if ($spId !== null) {
            CakupanDataSp::pastikanDapatDitulis($spId);
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
            'tahun_keluar' => self::angka($b, 'tahun_keluar'),
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
            // Sumber "KK Keluar per tahun" dashboard (rules.md 8g, dibalik 2026-09-04).
            'tahun_keluar' => [
                'nullable', 'integer', 'min:1900', 'max:'.date('Y'),
                'required_if:status_tinggal,Pindah Penduduk,Tidak Aktif',
            ],
            'telepon' => ['nullable', 'string', 'regex:/^(08|\+62)[0-9]{8,13}$/'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ], self::PESAN_UMUM + [
            'satuan_permukiman_id.required' => 'Kolom satuan_permukiman wajib diisi dan namanya harus sudah terdaftar.',
            'tahun_keluar.required_if' => 'Kolom tahun_keluar wajib diisi saat status_tinggal bukan Aktif.',
        ], [
            'nik' => 'nik', 'nama_kepala_keluarga' => 'nama_lengkap', 'no_kk' => 'no_kk',
            'satuan_permukiman_id' => 'satuan_permukiman', 'jenis_kelamin' => 'jenis_kelamin',
            'agama' => 'agama', 'tempat_lahir' => 'tempat_lahir', 'tanggal_lahir' => 'tanggal_lahir',
            'pendidikan_terakhir' => 'pendidikan_terakhir', 'pekerjaan_kepala_keluarga' => 'pekerjaan',
            'tahun_keluar' => 'tahun_keluar',
            'pendapatan_per_bulan' => 'pendapatan_per_bulan',
            'daerah_asal_kabupaten_id' => 'daerah_asal_kabupaten', 'tahun_kedatangan' => 'tahun_kedatangan',
            'status_tinggal' => 'status_tinggal', 'telepon' => 'telepon', 'keterangan' => 'keterangan',
        ]);

        if ($v->fails()) {
            return $v->errors()->first();
        }

        $tervalidasi = $v->validated();
        if ($tervalidasi['status_tinggal'] === StatusTinggal::Aktif->value) {
            $tervalidasi['tahun_keluar'] = null;
        }

        Transmigran::create($tervalidasi + ['uuid' => (string) Str::uuid()]);

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
        LayananNotifikasi::infrastrukturRusakBerat($infra);
        LayananNotifikasi::hitungUlangSp([$spId]);

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
            'jenis_fasilitas' => ValidationRules::daftarPilihan(JenisDaftarPilihan::JenisFasilitas, wajib: true),
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
        LayananNotifikasi::hitungUlangSp([$spId]);

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

    private static function kelompokPoktan(array $barisKelompok): array|string
    {
        $pertama = $barisKelompok[0][1];
        $profilKolom = array_slice(array_column(SkemaImpor::kolom('poktan'), 'kolom'), 0, 12);
        foreach ($barisKelompok as [, $baris]) {
            if (array_intersect_key($baris, array_flip($profilKolom)) != array_intersect_key($pertama, array_flip($profilKolom))) {
                return 'Profil Poktan yang diulang harus identik pada seluruh baris anggota.';
            }
        }

        $spId = self::wajibSp($pertama);
        if (is_string($spId)) {
            return $spId;
        }
        $asal = self::teks($pertama, 'asal_ketua');
        $ketuaId = null;
        $ketuaAnggotaId = null;
        if ($asal !== AsalWakilPoktan::BukanTransmigran->value) {
            $nikKetua = self::teks($pertama, 'nik_ketua');
            $ketuaId = $nikKetua === null ? null : self::cariIdTunggal(Transmigran::class, 'nik', $nikKetua);
            if (is_string($ketuaId)) {
                return $ketuaId;
            }
            if ($asal === AsalWakilPoktan::AnggotaKeluarga->value) {
                $ketuaAnggotaId = AnggotaKeluarga::query()->where('transmigran_id', $ketuaId)
                    ->where('nik', self::teks($pertama, 'nik_ketua'))->value('id_anggota_keluarga');
                if ($ketuaAnggotaId === null) {
                    return 'NIK ketua anggota keluarga tidak ditemukan pada keluarga yang dipilih.';
                }
            }
        }

        $profil = [
            'satuan_permukiman_id' => $spId, 'nama' => self::teks($pertama, 'nama_poktan'),
            'tahun_berdiri' => self::angka($pertama, 'tahun_berdiri'), 'asal_ketua' => $asal,
            'ketua_transmigran_id' => $ketuaId, 'ketua_anggota_keluarga_id' => $ketuaAnggotaId,
            'nama_ketua' => $asal === AsalWakilPoktan::BukanTransmigran->value ? self::teks($pertama, 'nama_ketua') : null,
            'nik_ketua' => $asal === AsalWakilPoktan::BukanTransmigran->value ? self::teks($pertama, 'nik_ketua') : null,
            'telepon_ketua' => self::teks($pertama, 'telepon_ketua'), 'email_ketua' => self::teks($pertama, 'email_ketua'),
            'alamat_ketua' => self::teks($pertama, 'alamat_ketua'),
            'luas_kering_ketua' => self::angka($pertama, 'luas_kering_ketua'),
            'luas_basah_ketua' => self::angka($pertama, 'luas_basah_ketua'), 'keterangan' => self::teks($pertama, 'keterangan'),
        ];
        $validator = Validator::make($profil, [
            'satuan_permukiman_id' => ['required', 'integer'], 'nama' => ['required', 'string', 'max:255'],
            'tahun_berdiri' => ['nullable', 'integer', 'min:1950', 'max:'.date('Y')],
            'asal_ketua' => ['required', Rule::enum(AsalWakilPoktan::class)],
            'ketua_transmigran_id' => ['nullable', 'integer', Rule::requiredIf($asal !== AsalWakilPoktan::BukanTransmigran->value)],
            'nama_ketua' => ['nullable', 'string', 'max:255', Rule::requiredIf($asal === AsalWakilPoktan::BukanTransmigran->value)],
            'nik_ketua' => ['nullable', 'digits:16', Rule::requiredIf($asal === AsalWakilPoktan::BukanTransmigran->value)],
            'telepon_ketua' => ['nullable', 'string', 'max:20'], 'email_ketua' => ['nullable', 'email:rfc', 'max:255'],
            'alamat_ketua' => ['nullable', 'string', 'max:255'], 'luas_kering_ketua' => ValidationRules::luas(wajib: false),
            'luas_basah_ketua' => ValidationRules::luas(wajib: false), 'keterangan' => ['nullable', 'string', 'max:1000'],
        ], self::PESAN_UMUM);
        if ($validator->fails()) {
            return $validator->errors()->first();
        }

        $anggota = [];
        foreach ($barisKelompok as [, $baris]) {
            $nik = self::teks($baris, 'nik_anggota');
            if ($nik === null) {
                continue;
            }
            $transmigranId = self::cariIdTunggal(Transmigran::class, 'nik', $nik);
            if (is_string($transmigranId)) {
                return $transmigranId;
            }
            $asalWakil = self::teks($baris, 'asal_wakil') ?? AsalWakilPoktan::KepalaKeluarga->value;
            $wakilId = null;
            if ($asalWakil === AsalWakilPoktan::AnggotaKeluarga->value) {
                $wakilId = AnggotaKeluarga::query()->where('transmigran_id', $transmigranId)
                    ->where('nik', self::teks($baris, 'nik_wakil'))->value('id_anggota_keluarga');
                if ($wakilId === null) {
                    return 'NIK wakil tidak ditemukan pada keluarga anggota.';
                }
            }
            $anggota[] = [
                'transmigran_id' => $transmigranId, 'asal_wakil' => $asalWakil,
                'anggota_keluarga_id' => $wakilId, 'jabatan' => self::teks($baris, 'jabatan_anggota') ?? 'Anggota',
                'tanggal_masuk' => self::teks($baris, 'tanggal_masuk'),
                'status' => self::teks($baris, 'status_anggota') ?? StatusKeaktifanAnggota::Aktif->value,
                'tanggal_keluar' => self::teks($baris, 'tanggal_keluar'), 'alasan_keluar' => self::teks($baris, 'alasan_keluar'),
                'keterangan' => self::teks($baris, 'keterangan_anggota'),
            ];
        }

        foreach ($anggota as $baris) {
            $v = Validator::make($baris, [
                'transmigran_id' => ['required', 'integer'],
                'asal_wakil' => ['required', Rule::in(AsalWakilPoktan::nilaiAnggota())],
                'anggota_keluarga_id' => ['nullable', 'integer', Rule::requiredIf($baris['asal_wakil'] === AsalWakilPoktan::AnggotaKeluarga->value)],
                'jabatan' => ValidationRules::daftarPilihan(JenisDaftarPilihan::JabatanAnggotaPoktan, wajib: true),
                'tanggal_masuk' => ['required', 'date', 'before_or_equal:today'],
                'status' => ['required', Rule::enum(StatusKeaktifanAnggota::class)],
                'tanggal_keluar' => ['nullable', 'date', 'after_or_equal:tanggal_masuk', Rule::requiredIf($baris['status'] === StatusKeaktifanAnggota::SudahKeluar->value)],
                'alasan_keluar' => ['nullable', 'string', 'max:255'],
                'keterangan' => ['nullable', 'string', 'max:255'],
            ], self::PESAN_UMUM);
            if ($v->fails()) {
                return $v->errors()->first();
            }
        }

        $existing = Poktan::withoutGlobalScopes()->where('nama', $profil['nama'])->first();
        if ($existing !== null) {
            $profilSama = array_intersect_key($existing->getRawOriginal(), $profil) == $profil;
            $kunciAnggota = array_flip(array_keys($anggota[0] ?? []));
            $anggotaSama = $existing->anggota()->withoutGlobalScopes()->orderBy('id_anggota_poktan')->get()
                ->map(fn ($baris) => array_intersect_key($baris->getRawOriginal(), $kunciAnggota))->all() == $anggota;

            return $profilSama && $anggotaSama ? ['status' => 'dilewati'] : 'Nama Poktan sudah ada dengan isi berbeda.';
        }

        OperasiPoktan::buat($validator->validated(), $anggota);

        return ['status' => 'dibuat'];
    }

    private static function kelompokSaprotan(array $barisKelompok): array|string
    {
        $pertama = $barisKelompok[0][1];
        $profilKolom = array_slice(array_column(SkemaImpor::kolom('saprotan'), 'kolom'), 0, 11);
        foreach ($barisKelompok as [, $baris]) {
            if (array_intersect_key($baris, array_flip($profilKolom)) != array_intersect_key($pertama, array_flip($profilKolom))) {
                return 'Profil pengadaan Saprotan yang diulang harus identik pada seluruh baris distribusi.';
            }
        }

        $satuanId = self::cariIdTunggal(Satuan::class, 'nama', (string) self::teks($pertama, 'satuan'));
        if (is_string($satuanId)) {
            return $satuanId;
        }
        $komoditasId = null;
        if (($nama = self::teks($pertama, 'komoditas')) !== null) {
            $komoditasId = self::cariIdTunggal(Komoditas::class, 'nama', $nama);
            if (is_string($komoditasId)) {
                return $komoditasId;
            }
        }

        $profil = [
            'kode_saprotan' => self::teks($pertama, 'kode_saprotan'),
            'jenis' => self::teks($pertama, 'jenis_saprotan'), 'nama' => self::teks($pertama, 'nama'),
            'jumlah_total' => self::angka($pertama, 'jumlah_total'), 'satuan_id' => $satuanId,
            'tahun_pengadaan' => self::angka($pertama, 'tahun_pengadaan'), 'komoditas_id' => $komoditasId,
            'varietas' => self::teks($pertama, 'varietas'), 'jadwal_tanam' => self::teks($pertama, 'jadwal_tanam'),
            'sumber_dana' => self::teks($pertama, 'sumber_dana'), 'keterangan' => self::teks($pertama, 'keterangan'),
        ];
        $benih = DaftarPilihan::memilikiPerilaku(JenisDaftarPilihan::JenisSaprotan, $profil['jenis'], 'benih');
        $validator = Validator::make($profil, [
            'kode_saprotan' => ['required', 'string', 'max:50'],
            'jenis' => ValidationRules::daftarPilihan(JenisDaftarPilihan::JenisSaprotan, wajib: true),
            'nama' => ['required', 'string', 'max:255'], 'jumlah_total' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'satuan_id' => ['required', 'integer'], 'tahun_pengadaan' => ValidationRules::tahun(wajib: true),
            'komoditas_id' => ['nullable', 'integer', Rule::requiredIf($benih)],
            'varietas' => ['nullable', 'string', 'max:120', Rule::requiredIf($benih)],
            'jadwal_tanam' => ['nullable', 'date_format:Y-m'],
            'sumber_dana' => ValidationRules::daftarPilihan(JenisDaftarPilihan::SumberDana),
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ], self::PESAN_UMUM);
        if ($validator->fails()) {
            return $validator->errors()->first();
        }

        $distribusi = [];
        foreach ($barisKelompok as [, $baris]) {
            $slug = self::teks($baris, 'poktan_slug');
            if ($slug === null) {
                continue;
            }
            $poktanId = self::cariIdTunggal(Poktan::class, 'slug', $slug);
            if (is_string($poktanId)) {
                return $poktanId;
            }
            CakupanDataSp::pastikanDapatDitulis(Poktan::findOrFail($poktanId));
            if (isset($distribusi[$poktanId])) {
                return 'Satu Poktan hanya boleh memiliki satu baris distribusi untuk kode Saprotan yang sama.';
            }
            $jumlah = self::angka($baris, 'jumlah_distribusi');
            if (! is_numeric($jumlah) || (float) $jumlah <= 0) {
                return 'Jumlah distribusi wajib lebih besar dari nol.';
            }
            $tanggalSerah = self::teks($baris, 'tanggal_serah');
            $v = Validator::make(['tanggal_serah' => $tanggalSerah], [
                'tanggal_serah' => ['nullable', 'date', 'before_or_equal:today'],
            ], self::PESAN_UMUM);
            if ($v->fails()) {
                return $v->errors()->first();
            }
            $distribusi[$poktanId] = [
                'poktan_id' => $poktanId, 'jumlah' => $jumlah,
                'tanggal_serah' => $tanggalSerah,
            ];
        }
        $distribusi = array_values($distribusi);

        $existing = Saprotan::with('distribusi')->where('kode_saprotan', $profil['kode_saprotan'])->first();
        if ($existing !== null) {
            $profilSama = array_intersect_key($existing->getRawOriginal(), $profil) == $profil;
            $kunci = array_flip(array_keys($distribusi[0] ?? []));
            $distribusiSama = $existing->distribusi->sortBy('id_saprotan_distribusi')->values()
                ->map(fn ($baris) => array_intersect_key($baris->getRawOriginal(), $kunci))->all() == $distribusi;

            return $profilSama && $distribusiSama ? ['status' => 'dilewati'] : 'Kode Saprotan sudah ada dengan isi berbeda.';
        }

        OperasiSaprotan::buat($validator->validated(), $distribusi);

        return ['status' => 'dibuat'];
    }

    /** @return array{status: string}|string */
    private static function barisPenanaman(array $baris): array|string
    {
        $kode = self::teks($baris, 'kode_penanaman');
        $slug = self::teks($baris, 'poktan_slug');
        $kodeSaprotan = self::teks($baris, 'kode_saprotan');
        $poktanId = $slug === null ? null : self::cariIdTunggal(Poktan::class, 'slug', $slug);
        if (is_string($poktanId)) {
            return $poktanId;
        }
        $saprotanId = $kodeSaprotan === null ? null : self::cariIdTunggal(Saprotan::class, 'kode_saprotan', $kodeSaprotan);
        if (is_string($saprotanId)) {
            return $saprotanId;
        }
        $distribusi = SaprotanDistribusi::query()->where('saprotan_id', $saprotanId)->where('poktan_id', $poktanId)->first();
        if ($distribusi === null) {
            return 'Kode Saprotan belum didistribusikan ke Poktan ini.';
        }

        $data = [
            'kode_penanaman' => $kode, 'volume_benih' => self::angka($baris, 'volume_benih'),
            'realisasi_tanam' => self::angka($baris, 'realisasi_tanam_ha'),
            'periode_tanam' => self::teks($baris, 'periode_tanam'), 'keterangan' => self::teks($baris, 'keterangan'),
        ];
        $validator = Validator::make($data, [
            'kode_penanaman' => ['required', 'string', 'max:50'],
            'volume_benih' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'realisasi_tanam' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'periode_tanam' => ['required', 'date_format:Y-m', 'before_or_equal:'.now()->format('Y-m')],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], self::PESAN_UMUM);
        if ($validator->fails()) {
            return $validator->errors()->first();
        }

        $existing = Penanaman::withoutGlobalScopes()->where('kode_penanaman', $kode)->first();
        $diharapkan = [
            'kode_penanaman' => $kode, 'poktan_id' => $poktanId, 'komoditas_id' => $distribusi->saprotan->komoditas_id,
            'saprotan_distribusi_id' => $distribusi->id_saprotan_distribusi, 'volume_benih' => $data['volume_benih'],
            'realisasi_tanam' => $data['realisasi_tanam'], 'periode_tanam' => $data['periode_tanam'], 'keterangan' => $data['keterangan'],
        ];
        if ($existing !== null) {
            return array_intersect_key($existing->getRawOriginal(), $diharapkan) == $diharapkan
                ? ['status' => 'dilewati'] : 'Kode Penanaman sudah ada dengan isi berbeda.';
        }

        $distribusi = OperasiPenanaman::distribusiTerkunci($distribusi->id_saprotan_distribusi);
        OperasiPenanaman::buat($validator->validated(), $distribusi);

        return ['status' => 'dibuat'];
    }

    /** @return array{status: string}|string */
    private static function barisHasilPanen(array $baris): array|string
    {
        $kode = self::teks($baris, 'kode_penanaman');
        $penanamanId = $kode === null ? null : self::cariIdTunggal(Penanaman::class, 'kode_penanaman', $kode);
        if (is_string($penanamanId)) {
            return $penanamanId;
        }
        $penanaman = Penanaman::query()->with('komoditas')->lockForUpdate()->findOrFail($penanamanId);
        $data = [
            'periode_panen' => self::teks($baris, 'periode_panen'),
            'realisasi_panen' => self::angka($baris, 'realisasi_panen_ha'), 'puso' => self::angka($baris, 'puso_ha'),
            'produktivitas' => self::angka($baris, 'produktivitas'), 'harga_jual' => self::angka($baris, 'harga_jual'),
            'keterangan' => self::teks($baris, 'keterangan'),
        ];
        $validator = Validator::make($data, [
            'periode_panen' => ['required', 'date_format:Y-m', 'before_or_equal:'.now()->format('Y-m')],
            'realisasi_panen' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'puso' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'produktivitas' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'harga_jual' => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ], self::PESAN_UMUM);
        if ($validator->fails()) {
            return $validator->errors()->first();
        }

        $aktif = HasilPanen::withoutGlobalScopes()->where('penanaman_id', $penanamanId)->where('status', 'Aktif')->first();
        $produktivitas = (float) ($data['produktivitas'] ?? 0);
        $diharapkan = [
            'penanaman_id' => $penanamanId, 'satuan_id' => $penanaman->komoditas->satuan_id,
            'periode_panen' => $data['periode_panen'], 'realisasi_panen' => $data['realisasi_panen'],
            'puso' => $data['puso'], 'produktivitas' => $produktivitas,
            'produksi' => round((float) $data['realisasi_panen'] * $produktivitas, 3),
            'harga_jual' => $data['harga_jual'], 'keterangan' => $data['keterangan'],
        ];
        if ($aktif !== null) {
            return array_intersect_key($aktif->getRawOriginal(), $diharapkan) == $diharapkan
                ? ['status' => 'dilewati'] : 'Penanaman sudah memiliki hasil panen aktif dengan isi berbeda.';
        }

        OperasiHasilPanen::buat($validator->validated(), $penanaman);

        return ['status' => 'dibuat'];
    }

    /** @return array{status: string}|string */
    private static function barisRumah(array $b): array|string
    {
        $spId = self::wajibSp($b);
        if (is_string($spId)) {
            return $spId;
        }

        $penghuniId = null;
        if (($nik = self::teks($b, 'nik_penghuni')) !== null) {
            $penghuniId = self::cariIdTunggal(Transmigran::class, 'nik', $nik);
            if (is_string($penghuniId)) {
                return $penghuniId;
            }
        }

        $data = [
            'satuan_permukiman_id' => $spId,
            'transmigran_id' => $penghuniId,
            'no_rumah' => self::teks($b, 'no_rumah'),
            'tahun_mulai_menghuni' => self::angka($b, 'tahun_mulai_menghuni'),
            'kondisi' => self::teks($b, 'kondisi'),
            'status_hunian' => self::teks($b, 'status_hunian'),
            'alasan_tidak_dihuni' => self::teks($b, 'alasan_tidak_dihuni'),
            'tahun_pembangunan' => self::angka($b, 'tahun_pembangunan'),
            'luas_bangunan' => self::angka($b, 'luas_bangunan'),
            'lintang' => self::angka($b, 'lintang'),
            'bujur' => self::angka($b, 'bujur'),
            'catatan_hunian' => self::teks($b, 'catatan_hunian'),
        ];

        if ($data['status_hunian'] === 'Tidak Dihuni') {
            $data['transmigran_id'] = null;
        }

        $validator = Validator::make($data, [
            'satuan_permukiman_id' => ['required', 'integer'],
            'transmigran_id' => ['nullable', 'integer', 'required_if:status_hunian,Dihuni', Rule::unique('rumah', 'transmigran_id')],
            'no_rumah' => ['required', 'string', 'max:50'],
            'tahun_mulai_menghuni' => ['nullable', 'integer', 'min:1900', 'max:'.date('Y'), 'required_if:status_hunian,Dihuni'],
            'kondisi' => ValidationRules::daftarPilihan(JenisDaftarPilihan::KondisiRumah, wajib: true),
            'status_hunian' => ValidationRules::daftarPilihan(JenisDaftarPilihan::StatusHunian, wajib: true),
            'alasan_tidak_dihuni' => ['nullable', 'string', 'max:2000', 'required_if:status_hunian,Tidak Dihuni'],
            'tahun_pembangunan' => ValidationRules::tahun(),
            'luas_bangunan' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'lintang' => ['required_with:bujur', ...ValidationRules::lintang()],
            'bujur' => ['required_with:lintang', ...ValidationRules::bujur()],
            'catatan_hunian' => ['nullable', 'string', 'max:2000'],
        ], self::PESAN_UMUM);

        $existing = Rumah::withoutGlobalScopes()->where([
            'satuan_permukiman_id' => $spId,
            'no_rumah' => $data['no_rumah'],
        ])->first();
        if ($existing !== null) {
            $diharapkan = array_diff_key($data, ['tahun_mulai_menghuni' => true]);
            $sama = $existing->only(array_keys($diharapkan)) == $diharapkan;
            $riwayatSama = $penghuniId === null || $existing->riwayatPenghunian()
                ->where('transmigran_id', $penghuniId)
                ->where('tahun_mulai_menghuni', $data['tahun_mulai_menghuni'])->exists();

            return $sama && $riwayatSama ? ['status' => 'dilewati'] : 'Nomor rumah sudah ada dengan isi berbeda.';
        }
        if ($validator->fails()) {
            return $validator->errors()->first();
        }
        if ($penghuniId !== null && Transmigran::findOrFail($penghuniId)->satuan_permukiman_id !== $spId) {
            return 'SP rumah wajib sama dengan SP keluarga penghuni.';
        }

        OperasiRumah::buat($validator->validated());

        return ['status' => 'dibuat'];
    }

    /** @return array{status: string}|string */
    private static function barisLahan(array $b): array|string
    {
        $spId = self::wajibSp($b);
        if (is_string($spId)) {
            return $spId;
        }
        $nik = self::teks($b, 'nik_pemilik');
        $pemilikId = $nik === null ? null : self::cariIdTunggal(Transmigran::class, 'nik', $nik);
        if (is_string($pemilikId)) {
            return $pemilikId;
        }

        $data = [
            'kode_lahan' => self::teks($b, 'kode_lahan'),
            'transmigran_id' => $pemilikId,
            'satuan_permukiman_id' => $spId,
            'luas_pekarangan' => self::angka($b, 'luas_pekarangan'),
            'lintang_pekarangan' => self::angka($b, 'lintang_pekarangan'),
            'bujur_pekarangan' => self::angka($b, 'bujur_pekarangan'),
            'luas_kering' => self::angka($b, 'luas_kering'),
            'luas_basah' => self::angka($b, 'luas_basah'),
            'lintang_usaha' => self::angka($b, 'lintang_usaha'),
            'bujur_usaha' => self::angka($b, 'bujur_usaha'),
            'tujuan_pemanfaatan' => self::teks($b, 'tujuan_pemanfaatan'),
            'status_sertifikat' => self::teks($b, 'status_sertifikat'),
            'keterangan' => self::teks($b, 'keterangan'),
        ];
        $validator = Validator::make($data, [
            'kode_lahan' => ['required', 'string', 'max:50'],
            'transmigran_id' => ['required', 'integer', Rule::unique('lahan', 'transmigran_id')],
            'satuan_permukiman_id' => ['required', 'integer'],
            'luas_pekarangan' => ['required_without_all:luas_kering,luas_basah', ...ValidationRules::luas()],
            'lintang_pekarangan' => ['required_with:bujur_pekarangan', ...ValidationRules::lintang()],
            'bujur_pekarangan' => ['required_with:lintang_pekarangan', ...ValidationRules::bujur()],
            'luas_kering' => ValidationRules::luas(wajib: false), 'luas_basah' => ValidationRules::luas(wajib: false),
            'lintang_usaha' => ['required_with:bujur_usaha', ...ValidationRules::lintang()],
            'bujur_usaha' => ['required_with:lintang_usaha', ...ValidationRules::bujur()],
            'tujuan_pemanfaatan' => ['nullable', 'string', 'max:2000'],
            'status_sertifikat' => ValidationRules::daftarPilihan(JenisDaftarPilihan::StatusSertifikat, wajib: true),
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ], self::PESAN_UMUM);

        $existing = Lahan::withoutGlobalScopes()->where('kode_lahan', $data['kode_lahan'])->first();
        if ($existing !== null) {
            $diharapkan = array_diff_key($data, ['status_sertifikat' => true]);
            $diharapkan['luas_usaha'] = ($data['luas_kering'] === null && $data['luas_basah'] === null)
                ? null : (float) ($data['luas_kering'] ?? 0) + (float) ($data['luas_basah'] ?? 0);
            $sama = $existing->only(array_keys($diharapkan)) == $diharapkan
                && $existing->transmigran?->status_sertifikat === $data['status_sertifikat'];

            return $sama ? ['status' => 'dilewati'] : 'Kode lahan sudah ada dengan isi berbeda.';
        }
        if ($validator->fails()) {
            return $validator->errors()->first();
        }
        if (Transmigran::findOrFail($pemilikId)->satuan_permukiman_id !== $spId) {
            return 'SP lahan wajib sama dengan SP keluarga pemilik.';
        }

        OperasiLahan::buat($validator->validated());

        return ['status' => 'dibuat'];
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

        $spId = self::cariIdTunggal(SatuanPermukiman::class, 'nama', $nama);
        if (is_int($spId)) {
            CakupanDataSp::pastikanDapatDitulis($spId);
        }

        return $spId;
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

    private static function boolean(array $b, string $kolom): bool|string|null
    {
        $nilai = Str::lower(self::teks($b, $kolom) ?? '');

        return match ($nilai) {
            '' => null,
            'ya', 'true', '1', 'yes' => true,
            'tidak', 'false', '0', 'no' => false,
            default => $nilai,
        };
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
