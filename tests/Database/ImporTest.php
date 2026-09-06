<?php

/*
 * Task 10.4 (1/2) -- mesin impor CSV, 8 entitas berdiri sendiri.
 *
 * Berjalan di MySQL nyata (App\Support\ImporEngine::proses menyentuh basis
 * data langsung, bukan lewat Request tervalidasi seperti controller lain).
 */

use App\Models\Alsintan;
use App\Models\Desa;
use App\Models\FasilitasSp;
use App\Models\HasilPanen;
use App\Models\Infrastruktur;
use App\Models\InventarisSp;
use App\Models\Komoditas;
use App\Models\Lahan;
use App\Models\Penanaman;
use App\Models\Permission;
use App\Models\Poktan;
use App\Models\Role;
use App\Models\Rumah;
use App\Models\Saprotan;
use App\Models\Satuan;
use App\Models\Transmigran;
use App\Models\User;
use App\Support\SkemaImpor;
use Database\Seeders\DaftarPilihanSeeder;
use Database\Seeders\KawasanSeeder;
use Database\Seeders\KomoditasSeeder;
use Database\Seeders\LahanSeeder;
use Database\Seeders\PenanamanSeeder;
use Database\Seeders\PoktanSeeder;
use Database\Seeders\SaprotanSeeder;
use Database\Seeders\SatuanSeeder;
use Database\Seeders\SpSeeder;
use Database\Seeders\TransmigranSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function berkasCsvImpor(string $isi): UploadedFile
{
    return UploadedFile::fake()->createWithContent('impor.csv', $isi);
}

function csvEntitasImpor(string $entitas, array $baris): string
{
    $kolom = array_column(SkemaImpor::kolom($entitas), 'kolom');
    $keluar = fopen('php://temp', 'w+');
    fputcsv($keluar, $kolom, ',', '"', '');
    foreach ($baris as $data) {
        fputcsv($keluar, array_map(fn (string $nama) => $data[$nama] ?? '', $kolom), ',', '"', '');
    }
    rewind($keluar);
    $isi = stream_get_contents($keluar);
    fclose($keluar);

    return $isi;
}

function berkasXlsxImpor(array $baris, ?callable $ubah = null): UploadedFile
{
    $buku = new Spreadsheet;
    $data = $buku->getActiveSheet()->setTitle('Data');
    $data->fromArray($baris, null, 'A1');
    if ($ubah !== null) {
        $ubah($buku, $data);
    }
    $path = tempnam(sys_get_temp_dir(), 'impor-xlsx-');
    (new Xlsx($buku))->save($path);
    $buku->disconnectWorksheets();

    return new UploadedFile($path, 'impor.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

function tambahEntriXlsx(UploadedFile $berkas, string $nama, string $isi): UploadedFile
{
    $zip = new ZipArchive;
    $zip->open($berkas->getRealPath());
    $zip->addFromString($nama, $isi);
    $zip->close();

    return $berkas;
}

beforeEach(function () {
    $this->seed(WilayahSeeder::class);
    $this->seed(KawasanSeeder::class);
    $this->seed(SpSeeder::class);
    $this->seed(SatuanSeeder::class);
    $this->seed(DaftarPilihanSeeder::class);

    $petugas = User::factory()->create();
    $petugas->semuaIzin = true;
    $this->actingAs($petugas);
});

it('mengimpor baris satuan yang sah dan menolak nama yang sudah dipakai', function () {
    $csv = "nama,simbol,faktor_ke_ton\nKarung,krg,\nTon,t,1\n";

    $r = $this->post(route('impor.unggah', 'satuan'), ['berkas' => berkasCsvImpor($csv)])->assertOk();

    expect($r->json('dibuat'))->toBe(1)
        ->and($r->json('gagal'))->toHaveCount(1)
        ->and($r->json('gagal.0.baris'))->toBe(3) // baris judul = 1, "Karung" = 2, "Ton" = 3
        ->and(Satuan::where('nama', 'Karung')->exists())->toBeTrue();
});

it('mengimpor baris wilayah (desa baru di bawah kecamatan yang sudah ada)', function () {
    $csv = "tingkat,nama,induk,kode\ndesa,Desa Uji Impor,Laen Manen,\n";

    $r = $this->post(route('impor.unggah', 'wilayah'), ['berkas' => berkasCsvImpor($csv)])->assertOk();

    expect($r->json())->toBe([
        'diproses' => 1, 'dibuat' => 1, 'dilewati' => 0, 'jumlah_gagal' => 0,
        'gagal' => [], 'galat_dibatasi' => false,
    ]);
    expect(Desa::where('nama', 'Desa Uji Impor')->exists())->toBeTrue();
});

it('menolak baris wilayah yang induknya tidak ditemukan', function () {
    $csv = "tingkat,nama,induk,kode\ndesa,Desa Uji Impor,Kecamatan Tidak Ada,\n";

    $r = $this->post(route('impor.unggah', 'wilayah'), ['berkas' => berkasCsvImpor($csv)])->assertOk();

    expect($r->json('dibuat'))->toBe(0)
        ->and($r->json('gagal.0.pesan'))->toContain('tidak ditemukan');
});

it('mengimpor baris komoditas dengan satuan baku dicari lewat nama', function () {
    $csv = "nama_komoditas,jenis,satuan_baku,unggulan,deskripsi\nSORGUM,Palawija,Kilogram,ya,\n";

    $r = $this->post(route('impor.unggah', 'komoditas'), ['berkas' => berkasCsvImpor($csv)])->assertOk();

    expect($r->json())->toBe([
        'diproses' => 1, 'dibuat' => 1, 'dilewati' => 0, 'jumlah_gagal' => 0,
        'gagal' => [], 'galat_dibatasi' => false,
    ]);
    $k = Komoditas::where('nama', 'SORGUM')->first();
    expect($k)->not->toBeNull()
        ->and($k->is_unggulan)->toBeTrue()
        ->and($k->satuan?->nama)->toBe('Kilogram');
});

it('mengimpor baris transmigran dengan SP dan kabupaten asal dicari lewat nama', function () {
    $csv = csvEntitasImpor('transmigran', [[
        'nik' => '5321019999999901', 'nama_lengkap' => 'BUDI SANTOSO',
        'no_kk' => '5321010102159901', 'satuan_permukiman' => 'SP Kapitan Meo',
        'jenis_kelamin' => 'Laki-laki', 'agama' => 'Katolik', 'tempat_lahir' => 'KUPANG',
        'tanggal_lahir' => '1985-01-01', 'pendidikan_terakhir' => 'SMA/SMK',
        'pekerjaan' => 'PETANI', 'pendapatan_per_bulan' => '2000000',
        'daerah_asal_kabupaten' => 'Kota Kupang', 'tahun_kedatangan' => '2020',
        'status_tinggal' => 'Aktif', 'telepon' => '081234500001',
    ]]);

    $r = $this->post(route('impor.unggah', 'transmigran'), ['berkas' => berkasCsvImpor($csv)])->assertOk();

    expect($r->json())->toBe([
        'diproses' => 1, 'dibuat' => 1, 'dilewati' => 0, 'jumlah_gagal' => 0,
        'gagal' => [], 'galat_dibatasi' => false,
    ]);
    $t = Transmigran::where('nik', '5321019999999901')->first();
    expect($t)->not->toBeNull()
        ->and($t->satuan_permukiman_id)->toBe(1)
        ->and($t->uuid)->not->toBeNull();
});

it('melewati baris transmigran yang SP-nya tidak ditemukan, sisanya tetap tersimpan', function () {
    $csv = csvEntitasImpor('transmigran', [
        [
            'nik' => '5321019999999902', 'nama_lengkap' => 'BUDI SATU',
            'no_kk' => '5321010102159902', 'satuan_permukiman' => 'SP Tidak Ada',
            'pekerjaan' => 'PETANI', 'tahun_kedatangan' => '2020', 'status_tinggal' => 'Aktif',
        ],
        [
            'nik' => '5321019999999903', 'nama_lengkap' => 'BUDI DUA',
            'no_kk' => '5321010102159903', 'satuan_permukiman' => 'SP Kapitan Meo',
            'pekerjaan' => 'PETANI', 'tahun_kedatangan' => '2020', 'status_tinggal' => 'Aktif',
        ],
    ]);

    $r = $this->post(route('impor.unggah', 'transmigran'), ['berkas' => berkasCsvImpor($csv)])->assertOk();

    expect($r->json('dibuat'))->toBe(1)
        ->and($r->json('gagal'))->toHaveCount(1)
        ->and($r->json('gagal.0.baris'))->toBe(2);
    expect(Transmigran::where('nik', '5321019999999903')->exists())->toBeTrue();
    expect(Transmigran::where('nik', '5321019999999902')->exists())->toBeFalse();
});

it('mewajibkan tahun_keluar pada impor transmigran begitu status_tinggal bukan Aktif', function () {
    $csv = csvEntitasImpor('transmigran', [
        [
            'nik' => '5321019999999904', 'nama_lengkap' => 'BUDI TIGA',
            'no_kk' => '5321010102159904', 'satuan_permukiman' => 'SP Kapitan Meo',
            'pekerjaan' => 'PETANI', 'tahun_kedatangan' => '2018', 'status_tinggal' => 'Pindah Penduduk',
        ],
        [
            'nik' => '5321019999999905', 'nama_lengkap' => 'BUDI EMPAT',
            'no_kk' => '5321010102159905', 'satuan_permukiman' => 'SP Kapitan Meo',
            'pekerjaan' => 'PETANI', 'tahun_kedatangan' => '2018',
            'status_tinggal' => 'Pindah Penduduk', 'tahun_keluar' => '2025',
        ],
    ]);

    $r = $this->post(route('impor.unggah', 'transmigran'), ['berkas' => berkasCsvImpor($csv)])->assertOk();

    expect($r->json('dibuat'))->toBe(1)
        ->and($r->json('gagal'))->toHaveCount(1)
        ->and($r->json('gagal.0.pesan'))->toContain('tahun_keluar');

    $tersimpan = Transmigran::where('nik', '5321019999999905')->first();
    expect($tersimpan)->not->toBeNull()
        ->and((int) $tersimpan->tahun_keluar)->toBe(2025);
});

it('mengimpor baris infrastruktur dan menautkan cakupan ke SP-nya sendiri', function () {
    $csv = "satuan_permukiman,nama_aset,jenis,kondisi,tahun_perolehan,sumber_dana,kapasitas,lintang,bujur,keterangan\n"
        ."SP Kapitan Meo,Jembatan Uji,Jalan Penghubung,Baik,2020,APBN,,,,\n";

    $r = $this->post(route('impor.unggah', 'infrastruktur'), ['berkas' => berkasCsvImpor($csv)])->assertOk();

    expect($r->json())->toBe([
        'diproses' => 1, 'dibuat' => 1, 'dilewati' => 0, 'jumlah_gagal' => 0,
        'gagal' => [], 'galat_dibatasi' => false,
    ]);
    $infra = Infrastruktur::where('nama', 'Jembatan Uji')->first();
    expect($infra)->not->toBeNull()
        ->and($infra->cakupan->pluck('id_satuan_permukiman')->all())->toBe([1]);
});

it('mengimpor baris inventaris-sp', function () {
    $csv = "satuan_permukiman,nama_barang,jumlah,satuan,status_penyerahan,kondisi,jenis_inventaris,tahun_perolehan,sumber_dana,keterangan\n"
        ."SP Kapitan Meo,Meja Kantor Uji,3,unit,Sudah Diserahkan,Baik,Perabotan,,,\n";

    $r = $this->post(route('impor.unggah', 'inventaris-sp'), ['berkas' => berkasCsvImpor($csv)])->assertOk();

    expect($r->json())->toBe([
        'diproses' => 1, 'dibuat' => 1, 'dilewati' => 0, 'jumlah_gagal' => 0,
        'gagal' => [], 'galat_dibatasi' => false,
    ]);
    expect(InventarisSp::where('nama_barang', 'Meja Kantor Uji')->exists())->toBeTrue();
});

it('mengimpor baris fasilitas-sp', function () {
    $csv = "satuan_permukiman,jenis_fasilitas,nama_fasilitas,jumlah,status_penyerahan,kondisi,tahun_perolehan,sumber_dana,lintang,bujur,keterangan\n"
        ."SP Kapitan Meo,Kesehatan,Posyandu Uji,1,Sudah Diserahkan,Baik,,,,,\n";

    $r = $this->post(route('impor.unggah', 'fasilitas-sp'), ['berkas' => berkasCsvImpor($csv)])->assertOk();

    expect($r->json())->toBe([
        'diproses' => 1, 'dibuat' => 1, 'dilewati' => 0, 'jumlah_gagal' => 0,
        'gagal' => [], 'galat_dibatasi' => false,
    ]);
    $fas = FasilitasSp::where('nama_fasilitas', 'Posyandu Uji')->first();
    expect($fas)->not->toBeNull()
        ->and($fas->cakupan->pluck('id_satuan_permukiman')->all())->toBe([1]);
});

it('mengimpor baris alsintan tanpa distribusi (belum tersalurkan)', function () {
    $csv = "jenis_alsintan,nama_alat,jumlah_total,tahun_pengadaan,sumber_dana,keterangan\n"
        ."Traktor Roda Dua,Traktor Uji,2,2024,APBN,\n";

    $r = $this->post(route('impor.unggah', 'alsintan'), ['berkas' => berkasCsvImpor($csv)])->assertOk();

    expect($r->json())->toBe([
        'diproses' => 1, 'dibuat' => 1, 'dilewati' => 0, 'jumlah_gagal' => 0,
        'gagal' => [], 'galat_dibatasi' => false,
    ]);
    $a = Alsintan::where('nama_alat', 'Traktor Uji')->first();
    expect($a)->not->toBeNull()
        ->and($a->distribusi)->toBeEmpty();
});

it('mengimpor rumah secara idempoten dan membuat riwayat hunian', function () {
    $this->seed(TransmigranSeeder::class);
    $csv = csvEntitasImpor('rumah', [[
        'no_rumah' => 'IMP-01', 'satuan_permukiman' => 'SP Kapitan Meo',
        'nik_penghuni' => '5321011505800001', 'tahun_mulai_menghuni' => 2020,
        'kondisi' => 'Tidak Rusak', 'status_hunian' => 'Dihuni',
        'tahun_pembangunan' => 2019, 'luas_bangunan' => 36,
    ]]);

    $this->post(route('impor.unggah', 'rumah'), ['berkas' => berkasCsvImpor($csv)])
        ->assertOk()->assertJsonPath('dibuat', 1);
    $this->post(route('impor.unggah', 'rumah'), ['berkas' => berkasCsvImpor($csv)])
        ->assertOk()->assertJsonPath('dilewati', 1)->assertJsonPath('jumlah_gagal', 0);

    $rumah = Rumah::where('no_rumah', 'IMP-01')->firstOrFail();
    expect($rumah->riwayatPenghunian)->toHaveCount(1)
        ->and((int) $rumah->riwayatPenghunian->first()->tahun_mulai_menghuni)->toBe(2020);
});

it('mengimpor lahan secara idempoten dan menurunkan sp serta luas usaha', function () {
    $this->seed(TransmigranSeeder::class);
    $csv = csvEntitasImpor('lahan', [[
        'kode_lahan' => 'IMP-LH-01', 'nik_pemilik' => '5321011505800001',
        'satuan_permukiman' => 'SP Kapitan Meo', 'luas_pekarangan' => 0.25,
        'luas_kering' => 1.25, 'luas_basah' => 0.25,
        'tujuan_pemanfaatan' => 'JAGUNG', 'status_sertifikat' => 'Sudah',
    ]]);

    $this->post(route('impor.unggah', 'lahan'), ['berkas' => berkasCsvImpor($csv)])
        ->assertOk()->assertJsonPath('dibuat', 1);
    $this->post(route('impor.unggah', 'lahan'), ['berkas' => berkasCsvImpor($csv)])
        ->assertOk()->assertJsonPath('dilewati', 1)->assertJsonPath('jumlah_gagal', 0);

    $lahan = Lahan::where('kode_lahan', 'IMP-LH-01')->firstOrFail();
    expect((float) $lahan->luas_usaha)->toBe(1.5)
        ->and($lahan->satuan_permukiman_id)->toBe(1);
});

it('mengimpor satu kelompok poktan beserta anggota secara atomik dan idempoten', function () {
    $this->seed(TransmigranSeeder::class);
    $profil = [
        'nama_poktan' => 'POKTAN IMPOR', 'satuan_permukiman' => 'SP Kapitan Meo',
        'tahun_berdiri' => 2020, 'asal_ketua' => 'Kepala Keluarga',
        'nik_ketua' => '5321011505800001',
    ];
    $csv = csvEntitasImpor('poktan', [
        $profil + ['nik_anggota' => '5321011505800001', 'jabatan_anggota' => 'Anggota', 'tanggal_masuk' => '2020-01-10', 'status_anggota' => 'Aktif'],
        $profil + ['nik_anggota' => '5321012203850002', 'jabatan_anggota' => 'Anggota', 'tanggal_masuk' => '2020-01-10', 'status_anggota' => 'Aktif'],
    ]);

    $respons = $this->post(route('impor.unggah', 'poktan'), ['berkas' => berkasCsvImpor($csv)])->assertOk();
    $respons->assertJsonPath('jumlah_gagal', 0)->assertJsonPath('dibuat', 2);
    $this->post(route('impor.unggah', 'poktan'), ['berkas' => berkasCsvImpor($csv)])
        ->assertOk()->assertJsonPath('dilewati', 2)->assertJsonPath('jumlah_gagal', 0);

    expect(Poktan::where('nama', 'POKTAN IMPOR')->firstOrFail()->anggota)->toHaveCount(2);
});

it('menggulung seluruh kelompok poktan bila satu anggota tidak sah', function () {
    $this->seed(TransmigranSeeder::class);
    $profil = [
        'nama_poktan' => 'POKTAN GAGAL ATOMIK', 'satuan_permukiman' => 'SP Kapitan Meo',
        'asal_ketua' => 'Kepala Keluarga', 'nik_ketua' => '5321011505800001',
    ];
    $csv = csvEntitasImpor('poktan', [
        $profil + ['nik_anggota' => '5321011505800001', 'jabatan_anggota' => 'Anggota', 'tanggal_masuk' => '2020-01-10', 'status_anggota' => 'Aktif'],
        $profil + ['nik_anggota' => '0000000000000000', 'jabatan_anggota' => 'Anggota', 'tanggal_masuk' => '2020-01-10', 'status_anggota' => 'Aktif'],
    ]);

    $this->post(route('impor.unggah', 'poktan'), ['berkas' => berkasCsvImpor($csv)])
        ->assertOk()->assertJsonPath('dibuat', 0)->assertJsonPath('jumlah_gagal', 2);

    expect(Poktan::where('nama', 'POKTAN GAGAL ATOMIK')->exists())->toBeFalse();
});

it('mengimpor satu pengadaan saprotan beserta distribusi secara atomik dan idempoten', function () {
    $this->seed(TransmigranSeeder::class);
    $this->seed(PoktanSeeder::class);
    $this->seed(KomoditasSeeder::class);
    $slug = Poktan::findOrFail(1)->slug;
    $profil = [
        'kode_saprotan' => 'SAP-IMP-01', 'jenis_saprotan' => 'Benih', 'nama' => 'BENIH IMPOR',
        'jumlah_total' => 100, 'satuan' => 'Kilogram', 'tahun_pengadaan' => 2026,
        'komoditas' => 'JAGUNG', 'varietas' => 'BISI UJI',
    ];
    $csv = csvEntitasImpor('saprotan', [
        $profil + ['poktan_slug' => $slug, 'jumlah_distribusi' => 60, 'tanggal_serah' => '2026-01-10'],
    ]);

    $this->post(route('impor.unggah', 'saprotan'), ['berkas' => berkasCsvImpor($csv)])
        ->assertOk()->assertJsonPath('dibuat', 1)->assertJsonPath('jumlah_gagal', 0);
    $this->post(route('impor.unggah', 'saprotan'), ['berkas' => berkasCsvImpor($csv)])
        ->assertOk()->assertJsonPath('dilewati', 1)->assertJsonPath('jumlah_gagal', 0);

    expect(Saprotan::where('kode_saprotan', 'SAP-IMP-01')->firstOrFail()->distribusi)->toHaveCount(1);
});

it('mengimpor penanaman dari kode stabil secara idempoten', function () {
    $this->seed(TransmigranSeeder::class);
    $this->seed(LahanSeeder::class);
    $this->seed(PoktanSeeder::class);
    $this->seed(KomoditasSeeder::class);
    $this->seed(SaprotanSeeder::class);
    $saprotan = Saprotan::where('jenis', 'Benih')->whereHas('distribusi')->firstOrFail();
    $distribusi = $saprotan->distribusi->first();
    $csv = csvEntitasImpor('penanaman', [[
        'kode_penanaman' => 'TAN-IMP-01', 'poktan_slug' => $distribusi->poktan->slug,
        'kode_saprotan' => $saprotan->kode_saprotan, 'periode_tanam' => '2026-01',
        'volume_benih' => 1, 'realisasi_tanam_ha' => 0.1,
    ]]);

    $this->post(route('impor.unggah', 'penanaman'), ['berkas' => berkasCsvImpor($csv)])
        ->assertOk()->assertJsonPath('dibuat', 1)->assertJsonPath('jumlah_gagal', 0);
    $this->post(route('impor.unggah', 'penanaman'), ['berkas' => berkasCsvImpor($csv)])
        ->assertOk()->assertJsonPath('dilewati', 1)->assertJsonPath('jumlah_gagal', 0);

    expect(Penanaman::where('kode_penanaman', 'TAN-IMP-01')->count())->toBe(1);
});

it('mengimpor hasil panen aktif dari kode penanaman secara idempoten', function () {
    $this->seed(TransmigranSeeder::class);
    $this->seed(LahanSeeder::class);
    $this->seed(PoktanSeeder::class);
    $this->seed(KomoditasSeeder::class);
    $this->seed(SaprotanSeeder::class);
    $this->seed(PenanamanSeeder::class);
    $penanaman = Penanaman::firstOrFail();
    $luas = (float) $penanaman->realisasi_tanam;
    $csv = csvEntitasImpor('hasil-panen', [[
        'kode_penanaman' => $penanaman->kode_penanaman, 'periode_panen' => '2026-06',
        'realisasi_panen_ha' => $luas, 'puso_ha' => 0, 'produktivitas' => 5,
    ]]);

    $this->post(route('impor.unggah', 'hasil-panen'), ['berkas' => berkasCsvImpor($csv)])
        ->assertOk()->assertJsonPath('dibuat', 1)->assertJsonPath('jumlah_gagal', 0);
    $this->post(route('impor.unggah', 'hasil-panen'), ['berkas' => berkasCsvImpor($csv)])
        ->assertOk()->assertJsonPath('dilewati', 1)->assertJsonPath('jumlah_gagal', 0);

    $panen = HasilPanen::where('penanaman_id', $penanaman->id_penanaman)->firstOrFail();
    expect((float) $panen->produksi)->toBe(round($luas * 5, 3));
});

it('menjaga paritas xlsx untuk enam entitas berantai', function () {
    $this->seed(TransmigranSeeder::class);
    $this->seed(KomoditasSeeder::class);

    $kirim = function (string $entitas, array $data): void {
        $kolom = array_column(SkemaImpor::kolom($entitas), 'kolom');
        $nilai = array_map(fn (string $nama) => $data[$nama] ?? '', $kolom);
        $respons = $this->post(route('impor.unggah', $entitas), ['berkas' => berkasXlsxImpor([$kolom, $nilai])])->assertOk();
        if ($respons->json('jumlah_gagal') !== 0) {
            throw new RuntimeException($entitas.': '.json_encode($respons->json(), JSON_PRETTY_PRINT));
        }
        $respons->assertJsonPath('dibuat', 1);
    };

    $kirim('rumah', [
        'no_rumah' => 'XLSX-01', 'satuan_permukiman' => 'SP Kapitan Meo',
        'kondisi' => 'Tidak Rusak', 'status_hunian' => 'Tidak Dihuni',
        'alasan_tidak_dihuni' => 'Belum ditempati',
    ]);
    $kirim('lahan', [
        'kode_lahan' => 'XLSX-LH-01', 'nik_pemilik' => '5321011505800001',
        'satuan_permukiman' => 'SP Kapitan Meo', 'luas_pekarangan' => 0.25,
        'status_sertifikat' => 'Sudah',
    ]);
    $kirim('poktan', [
        'nama_poktan' => 'POKTAN XLSX RANTAI', 'satuan_permukiman' => 'SP Kapitan Meo',
        'asal_ketua' => 'Bukan Transmigran', 'nik_ketua' => '5321019999999999',
        'nama_ketua' => 'KETUA XLSX', 'luas_kering_ketua' => 2,
    ]);
    $poktan = Poktan::where('nama', 'POKTAN XLSX RANTAI')->firstOrFail();
    $kirim('saprotan', [
        'kode_saprotan' => 'SAP-XLSX-RANTAI', 'jenis_saprotan' => 'Benih', 'nama' => 'BENIH XLSX',
        'jumlah_total' => 50, 'satuan' => 'Kilogram', 'tahun_pengadaan' => 2026,
        'komoditas' => 'JAGUNG', 'varietas' => 'XLSX', 'poktan_slug' => $poktan->slug,
        'jumlah_distribusi' => 20,
    ]);
    $kirim('penanaman', [
        'kode_penanaman' => 'TAN-XLSX-RANTAI', 'poktan_slug' => $poktan->slug,
        'kode_saprotan' => 'SAP-XLSX-RANTAI', 'periode_tanam' => '2026-01',
        'volume_benih' => 2, 'realisasi_tanam_ha' => 1,
    ]);
    $kirim('hasil-panen', [
        'kode_penanaman' => 'TAN-XLSX-RANTAI', 'periode_panen' => '2026-06',
        'realisasi_panen_ha' => 0.9, 'puso_ha' => 0.1, 'produktivitas' => 4,
    ]);
});

it('mengabaikan baris petunjuk # dan baris kosong pada berkas template', function () {
    $csv = "\xEF\xBB\xBF# TEMPLATE IMPOR DATA MASTER SATUAN\n"
        ."# nama (wajib) -- Nama satuan, unik\n"
        ."\n"
        ."nama,simbol,faktor_ke_ton\n"
        ."Karung,krg,\n";

    $r = $this->post(route('impor.unggah', 'satuan'), ['berkas' => berkasCsvImpor($csv)])->assertOk();

    expect($r->json())->toBe([
        'diproses' => 1, 'dibuat' => 1, 'dilewati' => 0, 'jumlah_gagal' => 0,
        'gagal' => [], 'galat_dibatasi' => false,
    ]);
});

it('menjaga paritas xlsx dengan csv untuk delapan entitas aktif', function (string $entitas, array $data) {
    $kolom = array_column(SkemaImpor::kolom($entitas), 'kolom');
    $nilai = array_map(fn (string $nama) => $data[$nama] ?? '', $kolom);

    $this->post(route('impor.unggah', $entitas), [
        'berkas' => berkasXlsxImpor([$kolom, $nilai]),
    ])->assertOk()->assertJsonPath('dibuat', 1)->assertJsonPath('jumlah_gagal', 0);
})->with([
    'satuan' => ['satuan', ['nama' => 'Pikul', 'simbol' => 'pkl', 'faktor_ke_ton' => 0.1]],
    'wilayah' => ['wilayah', ['tingkat' => 'desa', 'nama' => 'Desa XLSX', 'induk' => 'Laen Manen']],
    'komoditas' => ['komoditas', ['nama_komoditas' => 'SORGUM XLSX', 'jenis' => 'Palawija', 'satuan_baku' => 'Kilogram', 'unggulan' => 'tidak']],
    'transmigran' => ['transmigran', [
        'nik' => '5321019999999701', 'nama_lengkap' => 'BUDI XLSX', 'no_kk' => '5321010102159701',
        'satuan_permukiman' => 'SP Kapitan Meo', 'pekerjaan' => 'PETANI',
        'tahun_kedatangan' => '2020', 'status_tinggal' => 'Aktif',
    ]],
    'infrastruktur' => ['infrastruktur', [
        'satuan_permukiman' => 'SP Kapitan Meo', 'nama_aset' => 'Jalan XLSX',
        'jenis' => 'Jalan Penghubung', 'kondisi' => 'Baik',
    ]],
    'inventaris-sp' => ['inventaris-sp', [
        'satuan_permukiman' => 'SP Kapitan Meo', 'nama_barang' => 'Meja XLSX', 'jumlah' => 2,
        'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik', 'jenis_inventaris' => 'Perabotan',
    ]],
    'fasilitas-sp' => ['fasilitas-sp', [
        'satuan_permukiman' => 'SP Kapitan Meo', 'jenis_fasilitas' => 'Kesehatan',
        'nama_fasilitas' => 'Posyandu XLSX', 'jumlah' => 1,
        'status_penyerahan' => 'Sudah Diserahkan', 'kondisi' => 'Baik',
    ]],
    'alsintan' => ['alsintan', [
        'jenis_alsintan' => 'Traktor Roda Dua', 'nama_alat' => 'Traktor XLSX',
        'jumlah_total' => 2, 'tahun_pengadaan' => 2024,
    ]],
]);

it('menolak format yang tidak didukung termasuk xls xlsm xlsb dan txt', function (string $ekstensi) {
    $berkas = UploadedFile::fake()->create('data.'.$ekstensi, 10);

    $this->post(route('impor.unggah', 'satuan'), ['berkas' => $berkas])
        ->assertStatus(422)
        ->assertJsonFragment(['pesan' => 'Berkas harus berformat XLSX (.xlsx) atau CSV (.csv).']);
})->with(['xls', 'xlsm', 'xlsb', 'txt']);

it('menolak formula dan struktur xlsx yang tidak aman', function (callable $berkas, string $pesan) {
    $this->post(route('impor.unggah', 'satuan'), ['berkas' => $berkas()])
        ->assertStatus(422)
        ->assertJsonPath('pesan', fn (string $nilai): bool => str_contains($nilai, $pesan));
})->with([
    'formula' => [fn () => berkasXlsxImpor([
        ['nama', 'simbol', 'faktor_ke_ton'],
        ['Karung Formula', 'krg', '=1+1'],
    ]), 'Formula'],
    'sheet data tambahan' => [fn () => berkasXlsxImpor([
        ['nama', 'simbol', 'faktor_ke_ton'],
        ['Karung Sheet', 'krg', null],
    ], fn (Spreadsheet $buku) => $buku->createSheet()->setTitle('Data 2')), 'hanya boleh memuat'],
    'tautan eksternal' => [fn () => tambahEntriXlsx(berkasXlsxImpor([
        ['nama', 'simbol', 'faktor_ke_ton'],
        ['Karung Link', 'krg', null],
    ]), 'xl/externalLinks/externalLink1.xml', '<externalLink/>'), 'tautan eksternal'],
    'makro' => [fn () => tambahEntriXlsx(berkasXlsxImpor([
        ['nama', 'simbol', 'faktor_ke_ton'],
        ['Karung Makro', 'krg', null],
    ]), 'xl/vbaProject.bin', 'macro'), 'makro'],
]);

it('menolak xlsx rusak palsu dan sel ekstrem sparse', function (UploadedFile $berkas, string $pesan) {
    $this->post(route('impor.unggah', 'satuan'), ['berkas' => $berkas])
        ->assertStatus(422)
        ->assertJsonPath('pesan', fn (string $nilai): bool => str_contains($nilai, $pesan));
})->with([
    'palsu' => [UploadedFile::fake()->createWithContent('palsu.xlsx', 'bukan zip'), 'XLSX rusak'],
    'sparse' => [berkasXlsxImpor([
        ['nama', 'simbol', 'faktor_ke_ton'],
        ['Karung Sparse', 'krg', null],
    ], fn (Spreadsheet $buku, $data) => $data->setCellValue('XFD1048576', 'ekstrem')), 'melampaui batas'],
]);

it('menolak judul duplikat hilang asing dan berkas tanpa data', function (string $csv, string $pesan) {
    $this->post(route('impor.unggah', 'satuan'), ['berkas' => berkasCsvImpor($csv)])
        ->assertStatus(422)
        ->assertJsonPath('pesan', fn (string $nilai): bool => str_contains($nilai, $pesan));
})->with([
    'duplikat' => ["nama,nama,faktor_ke_ton\nKarung,krg,\n", 'duplikat'],
    'hilang' => ["nama,simbol\nKarung,krg\n", 'hilang'],
    'asing' => ["nama,simbol,asing\nKarung,krg,x\n", 'tidak dikenal'],
    'tanpa data' => ["nama,simbol,faktor_ke_ton\n", 'hanya berisi judul'],
]);

it('menerima urutan judul fleksibel dan menolak boolean yang tidak dikenal', function () {
    $this->post(route('impor.unggah', 'satuan'), [
        'berkas' => berkasCsvImpor("simbol,faktor_ke_ton,nama\nkrg,,Karung Fleksibel\n"),
    ])->assertOk()->assertJsonPath('dibuat', 1);

    $this->post(route('impor.unggah', 'komoditas'), [
        'berkas' => berkasCsvImpor("nama_komoditas,jenis,satuan_baku,unggulan,deskripsi\nKACANG UJI,Palawija,Kilogram,mungkin,\n"),
    ])->assertOk()->assertJsonPath('jumlah_gagal', 1);
});

it('menerima tepat 1000 baris dan membatasi rincian galat', function () {
    $csv = "nama,simbol,faktor_ke_ton\n".str_repeat(",x,\n", 1000);

    $this->post(route('impor.unggah', 'satuan'), ['berkas' => berkasCsvImpor($csv)])
        ->assertOk()
        ->assertJsonPath('diproses', 1000)
        ->assertJsonPath('jumlah_gagal', 1000)
        ->assertJsonCount(100, 'gagal')
        ->assertJsonPath('galat_dibatasi', true);
});

it('menolak lebih dari 1000 baris data', function () {
    $csv = "nama,simbol,faktor_ke_ton\n".str_repeat(",x,\n", 1001);

    $this->post(route('impor.unggah', 'satuan'), ['berkas' => berkasCsvImpor($csv)])
        ->assertStatus(422)
        ->assertJsonPath('pesan', fn (string $nilai): bool => str_contains($nilai, '1000'));
});

it('menolak entitas yang tidak dikenal', function () {
    $this->post(route('impor.unggah', 'tidak-ada'), ['berkas' => berkasCsvImpor("a\nb\n")])
        ->assertNotFound();
});

it('menolak sel berformula pada berkas CSV, bukan hanya XLSX', function (string $selJahat) {
    $csv = "nama,simbol,faktor_ke_ton\n\"{$selJahat}\",krg,\n";

    $this->post(route('impor.unggah', 'satuan'), ['berkas' => berkasCsvImpor($csv)])
        ->assertStatus(422)
        ->assertJsonPath('pesan', fn (string $nilai): bool => str_contains($nilai, 'Formula tidak diizinkan'));
})->with([
    'sama dengan' => '=SUM(A1:A2)',
    'plus' => '+1+1',
    'minus non-numerik' => '-2+3+cmd',
    'at' => '@SUM(1)',
]);

it('menolak pengguna tanpa kewenangan lihat atau tambah pada entitas terkait', function (array $aksi) {
    $role = Role::factory()->create();
    foreach ($aksi as $namaAksi) {
        $izin = Permission::factory()->create([
            'nama' => 'satuan.'.$namaAksi,
            'modul' => 'satuan',
            'aksi' => $namaAksi,
        ]);
        $role->permissions()->attach($izin);
    }
    $pengguna = User::factory()->create(['role_id' => $role->id_role]);

    $this->actingAs($pengguna)
        ->post(route('impor.unggah', 'satuan'), [
            'berkas' => berkasCsvImpor("nama,simbol,faktor_ke_ton\nKarung Baru,krg,\n"),
        ])->assertForbidden();
})->with([
    'tanpa keduanya' => [[]],
    'hanya lihat' => [['lihat']],
    'hanya tambah' => [['tambah']],
]);

it('mewajibkan izin tambah anggota untuk impor poktan', function () {
    $role = Role::factory()->create();
    foreach (['lihat', 'tambah'] as $aksi) {
        $role->permissions()->attach(Permission::factory()->create([
            'nama' => 'poktan.'.$aksi, 'modul' => 'poktan', 'aksi' => $aksi,
        ]));
    }
    $pengguna = User::factory()->create(['role_id' => $role->id_role]);
    $csv = csvEntitasImpor('poktan', [[
        'nama_poktan' => 'POKTAN TANPA IZIN ANGGOTA', 'satuan_permukiman' => 'SP Kapitan Meo',
        'asal_ketua' => 'Bukan Transmigran', 'nik_ketua' => '5321019999999999', 'nama_ketua' => 'KETUA UJI',
    ]]);

    $this->actingAs($pengguna)
        ->post(route('impor.unggah', 'poktan'), ['berkas' => berkasCsvImpor($csv)])
        ->assertForbidden();
    expect(Poktan::where('nama', 'POKTAN TANPA IZIN ANGGOTA')->exists())->toBeFalse();
});

it('melindungi unduhan template dengan izin lihat dan tambah', function (array $aksi) {
    $role = Role::factory()->create();
    foreach ($aksi as $namaAksi) {
        $role->permissions()->attach(Permission::factory()->create([
            'nama' => 'satuan.'.$namaAksi, 'modul' => 'satuan', 'aksi' => $namaAksi,
        ]));
    }
    $pengguna = User::factory()->create(['role_id' => $role->id_role]);

    $this->actingAs($pengguna)->get(route('template-impor', 'satuan'))->assertForbidden();
    $this->actingAs($pengguna)->get(route('template-impor.xlsx', 'satuan'))->assertForbidden();
})->with([
    'tanpa keduanya' => [[]],
    'hanya lihat' => [['lihat']],
    'hanya tambah' => [['tambah']],
]);

it('menormalisasi tanggal yang dideklarasikan skema pada csv dan xlsx', function (string $tanggal, string $format) {
    $nik = $format === 'csv' ? '5321019999999601' : '5321019999999602';
    $kk = $format === 'csv' ? '5321010102159601' : '5321010102159602';
    $data = [
        'nik' => $nik, 'nama_lengkap' => 'BUDI TANGGAL', 'no_kk' => $kk,
        'satuan_permukiman' => 'SP Kapitan Meo', 'tanggal_lahir' => $tanggal,
        'pekerjaan' => 'PETANI', 'tahun_kedatangan' => '2020', 'status_tinggal' => 'Aktif',
    ];
    $kolom = array_column(SkemaImpor::kolom('transmigran'), 'kolom');
    $berkas = $format === 'csv'
        ? berkasCsvImpor(csvEntitasImpor('transmigran', [$data]))
        : berkasXlsxImpor([$kolom, array_map(fn (string $nama) => $data[$nama] ?? '', $kolom)]);

    $this->post(route('impor.unggah', 'transmigran'), ['berkas' => $berkas])
        ->assertOk()->assertJsonPath('dibuat', 1);

    expect(Transmigran::where('nik', $nik)->firstOrFail()->tanggal_lahir->format('Y-m-d'))->toBe('1985-01-02');
})->with([
    'csv' => ['02/01/1985', 'csv'],
    'xlsx' => ['02-01-1985', 'xlsx'],
]);

it('menjaga nik kk dan telepon sebagai teks termasuk nol awal pada xlsx', function () {
    $kolom = array_column(SkemaImpor::kolom('transmigran'), 'kolom');
    $data = array_fill(0, count($kolom), '');
    foreach ([
        'nik' => '0321019999999901', 'nama_lengkap' => 'BUDI NOL',
        'no_kk' => '0021010102159901', 'satuan_permukiman' => 'SP Kapitan Meo',
        'pekerjaan' => 'PETANI', 'tahun_kedatangan' => '2020',
        'status_tinggal' => 'Aktif', 'telepon' => '081234500009',
    ] as $nama => $nilai) {
        $data[array_search($nama, $kolom, true)] = $nilai;
    }

    $this->post(route('impor.unggah', 'transmigran'), [
        'berkas' => berkasXlsxImpor([$kolom, $data]),
    ])->assertOk()->assertJsonPath('dibuat', 1);

    $transmigran = Transmigran::where('nik', '0321019999999901')->firstOrFail();
    expect($transmigran->no_kk)->toBe('0021010102159901')
        ->and($transmigran->telepon)->toBe('081234500009');
});

it('menegakkan cakupan sp bagi aktor yang ditugaskan dan tidak ditugaskan', function () {
    $role = Role::factory()->create(['cakupan_data' => 'Per SP']);
    foreach (['lihat', 'tambah'] as $namaAksi) {
        $izin = Permission::factory()->create([
            'nama' => 'transmigran.'.$namaAksi,
            'modul' => 'transmigran',
            'aksi' => $namaAksi,
        ]);
        $role->permissions()->attach($izin);
    }
    $pengguna = User::factory()->create(['role_id' => $role->id_role]);
    $pengguna->satuanPermukiman()->attach(1);
    $this->actingAs($pengguna);

    $csv = csvEntitasImpor('transmigran', [
        [
            'nik' => '5321019999999801', 'nama_lengkap' => 'DALAM CAKUPAN',
            'no_kk' => '5321010102159801', 'satuan_permukiman' => 'SP Kapitan Meo',
            'pekerjaan' => 'PETANI', 'tahun_kedatangan' => '2020', 'status_tinggal' => 'Aktif',
        ],
        [
            'nik' => '5321019999999802', 'nama_lengkap' => 'LUAR CAKUPAN',
            'no_kk' => '5321010102159802', 'satuan_permukiman' => 'SP Tniumanu',
            'pekerjaan' => 'PETANI', 'tahun_kedatangan' => '2020', 'status_tinggal' => 'Aktif',
        ],
    ]);

    $this->post(route('impor.unggah', 'transmigran'), ['berkas' => berkasCsvImpor($csv)])
        ->assertOk()->assertJsonPath('dibuat', 1)->assertJsonPath('jumlah_gagal', 1);

    expect(Transmigran::withoutGlobalScopes()->where('nik', '5321019999999801')->exists())->toBeTrue()
        ->and(Transmigran::withoutGlobalScopes()->where('nik', '5321019999999802')->exists())->toBeFalse();
});

it('menggulung baris gagal tetapi melanjutkan baris berikutnya', function () {
    Infrastruktur::created(function (Infrastruktur $model): void {
        if ($model->nama === 'Aset Gagal') {
            throw new RuntimeException('uji rollback');
        }
    });

    $csv = "satuan_permukiman,nama_aset,jenis,kondisi,tahun_perolehan,sumber_dana,kapasitas,lintang,bujur,keterangan\n"
        ."SP Kapitan Meo,Aset Gagal,Jalan Penghubung,Baik,2020,APBN,,,,\n"
        ."SP Kapitan Meo,Aset Lanjut,Jalan Penghubung,Baik,2020,APBN,,,,\n";

    $this->post(route('impor.unggah', 'infrastruktur'), ['berkas' => berkasCsvImpor($csv)])
        ->assertOk()->assertJsonPath('dibuat', 1)->assertJsonPath('jumlah_gagal', 1);

    expect(Infrastruktur::where('nama', 'Aset Gagal')->exists())->toBeFalse()
        ->and(Infrastruktur::where('nama', 'Aset Lanjut')->exists())->toBeTrue();
});
