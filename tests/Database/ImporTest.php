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
use App\Models\Infrastruktur;
use App\Models\InventarisSp;
use App\Models\Komoditas;
use App\Models\Role;
use App\Models\Satuan;
use App\Models\Transmigran;
use App\Models\User;
use Database\Seeders\DaftarPilihanSeeder;
use Database\Seeders\KawasanSeeder;
use Database\Seeders\SatuanSeeder;
use Database\Seeders\SpSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Http\UploadedFile;

function berkasCsvImpor(string $isi): UploadedFile
{
    return UploadedFile::fake()->createWithContent('impor.csv', $isi);
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

    expect($r->json('disimpan'))->toBe(1)
        ->and($r->json('gagal'))->toHaveCount(1)
        ->and($r->json('gagal.0.baris'))->toBe(3) // baris judul = 1, "Karung" = 2, "Ton" = 3
        ->and(Satuan::where('nama', 'Karung')->exists())->toBeTrue();
});

it('mengimpor baris wilayah (desa baru di bawah kecamatan yang sudah ada)', function () {
    $csv = "tingkat,nama,induk,kode\ndesa,Desa Uji Impor,Laen Manen,\n";

    $r = $this->post(route('impor.unggah', 'wilayah'), ['berkas' => berkasCsvImpor($csv)])->assertOk();

    expect($r->json())->toBe(['disimpan' => 1, 'gagal' => []]);
    expect(Desa::where('nama', 'Desa Uji Impor')->exists())->toBeTrue();
});

it('menolak baris wilayah yang induknya tidak ditemukan', function () {
    $csv = "tingkat,nama,induk,kode\ndesa,Desa Uji Impor,Kecamatan Tidak Ada,\n";

    $r = $this->post(route('impor.unggah', 'wilayah'), ['berkas' => berkasCsvImpor($csv)])->assertOk();

    expect($r->json('disimpan'))->toBe(0)
        ->and($r->json('gagal.0.pesan'))->toContain('tidak ditemukan');
});

it('mengimpor baris komoditas dengan satuan baku dicari lewat nama', function () {
    $csv = "nama_komoditas,jenis,satuan_baku,unggulan,deskripsi\nSORGUM,Palawija,Kilogram,ya,\n";

    $r = $this->post(route('impor.unggah', 'komoditas'), ['berkas' => berkasCsvImpor($csv)])->assertOk();

    expect($r->json())->toBe(['disimpan' => 1, 'gagal' => []]);
    $k = Komoditas::where('nama', 'SORGUM')->first();
    expect($k)->not->toBeNull()
        ->and($k->is_unggulan)->toBeTrue()
        ->and($k->satuan?->nama)->toBe('Kilogram');
});

it('mengimpor baris transmigran dengan SP dan kabupaten asal dicari lewat nama', function () {
    $csv = 'nik,nama_lengkap,no_kk,satuan_permukiman,jenis_kelamin,agama,tempat_lahir,tanggal_lahir,'
        .'pendidikan_terakhir,pekerjaan,pendapatan_per_bulan,daerah_asal_kabupaten,tahun_kedatangan,'
        ."status_tinggal,telepon,keterangan\n"
        .'5321019999999901,BUDI SANTOSO,5321010102159901,SP Kapitan Meo,Laki-laki,Katolik,KUPANG,'
        ."1985-01-01,SMA/SMK,PETANI,2000000,Kota Kupang,2020,Aktif,081234500001,\n";

    $r = $this->post(route('impor.unggah', 'transmigran'), ['berkas' => berkasCsvImpor($csv)])->assertOk();

    expect($r->json())->toBe(['disimpan' => 1, 'gagal' => []]);
    $t = Transmigran::where('nik', '5321019999999901')->first();
    expect($t)->not->toBeNull()
        ->and($t->satuan_permukiman_id)->toBe(1)
        ->and($t->uuid)->not->toBeNull();
});

it('melewati baris transmigran yang SP-nya tidak ditemukan, sisanya tetap tersimpan', function () {
    $csv = "nik,nama_lengkap,no_kk,satuan_permukiman,pekerjaan,tahun_kedatangan,status_tinggal\n"
        ."5321019999999902,BUDI SATU,5321010102159902,SP Tidak Ada,PETANI,2020,Aktif\n"
        ."5321019999999903,BUDI DUA,5321010102159903,SP Kapitan Meo,PETANI,2020,Aktif\n";

    $r = $this->post(route('impor.unggah', 'transmigran'), ['berkas' => berkasCsvImpor($csv)])->assertOk();

    expect($r->json('disimpan'))->toBe(1)
        ->and($r->json('gagal'))->toHaveCount(1)
        ->and($r->json('gagal.0.baris'))->toBe(2);
    expect(Transmigran::where('nik', '5321019999999903')->exists())->toBeTrue();
    expect(Transmigran::where('nik', '5321019999999902')->exists())->toBeFalse();
});

it('mengimpor baris infrastruktur dan menautkan cakupan ke SP-nya sendiri', function () {
    $csv = "satuan_permukiman,nama_aset,jenis,kondisi,tahun_perolehan,sumber_dana,kapasitas,lintang,bujur,keterangan\n"
        ."SP Kapitan Meo,Jembatan Uji,Jalan Penghubung,Baik,2020,APBN,,,,\n";

    $r = $this->post(route('impor.unggah', 'infrastruktur'), ['berkas' => berkasCsvImpor($csv)])->assertOk();

    expect($r->json())->toBe(['disimpan' => 1, 'gagal' => []]);
    $infra = Infrastruktur::where('nama', 'Jembatan Uji')->first();
    expect($infra)->not->toBeNull()
        ->and($infra->cakupan->pluck('id_satuan_permukiman')->all())->toBe([1]);
});

it('mengimpor baris inventaris-sp', function () {
    $csv = "satuan_permukiman,nama_barang,jumlah,satuan,status_penyerahan,kondisi,jenis_inventaris,tahun_perolehan,sumber_dana,keterangan\n"
        ."SP Kapitan Meo,Meja Kantor Uji,3,unit,Sudah Diserahkan,Baik,Perabotan,,,\n";

    $r = $this->post(route('impor.unggah', 'inventaris-sp'), ['berkas' => berkasCsvImpor($csv)])->assertOk();

    expect($r->json())->toBe(['disimpan' => 1, 'gagal' => []]);
    expect(InventarisSp::where('nama_barang', 'Meja Kantor Uji')->exists())->toBeTrue();
});

it('mengimpor baris fasilitas-sp', function () {
    $csv = "satuan_permukiman,jenis_fasilitas,nama_fasilitas,jumlah,status_penyerahan,kondisi,tahun_perolehan,sumber_dana,lintang,bujur,keterangan\n"
        ."SP Kapitan Meo,Kesehatan,Posyandu Uji,1,Sudah Diserahkan,Baik,,,,,\n";

    $r = $this->post(route('impor.unggah', 'fasilitas-sp'), ['berkas' => berkasCsvImpor($csv)])->assertOk();

    expect($r->json())->toBe(['disimpan' => 1, 'gagal' => []]);
    $fas = FasilitasSp::where('nama_fasilitas', 'Posyandu Uji')->first();
    expect($fas)->not->toBeNull()
        ->and($fas->cakupan->pluck('id_satuan_permukiman')->all())->toBe([1]);
});

it('mengimpor baris alsintan tanpa distribusi (belum tersalurkan)', function () {
    $csv = "jenis_alsintan,nama_alat,jumlah_total,tahun_pengadaan,sumber_dana,keterangan\n"
        ."Traktor Roda Dua,Traktor Uji,2,2024,APBN,\n";

    $r = $this->post(route('impor.unggah', 'alsintan'), ['berkas' => berkasCsvImpor($csv)])->assertOk();

    expect($r->json())->toBe(['disimpan' => 1, 'gagal' => []]);
    $a = Alsintan::where('nama_alat', 'Traktor Uji')->first();
    expect($a)->not->toBeNull()
        ->and($a->distribusi)->toBeEmpty();
});

it('mengabaikan baris petunjuk # dan baris kosong pada berkas template', function () {
    $csv = "\xEF\xBB\xBF# TEMPLATE IMPOR DATA MASTER SATUAN\n"
        ."# nama (wajib) -- Nama satuan, unik\n"
        ."\n"
        ."nama,simbol,faktor_ke_ton\n"
        ."Karung,krg,\n";

    $r = $this->post(route('impor.unggah', 'satuan'), ['berkas' => berkasCsvImpor($csv)])->assertOk();

    expect($r->json())->toBe(['disimpan' => 1, 'gagal' => []]);
});

it('menolak berkas berformat selain csv', function () {
    $berkas = UploadedFile::fake()->create('data.xlsx', 10);

    $r = $this->post(route('impor.unggah', 'satuan'), ['berkas' => $berkas]);

    $r->assertStatus(422)->assertJsonFragment(['pesan' => 'Hanya berkas CSV yang didukung saat ini. Simpan Excel sebagai CSV (UTF-8) lalu unggah ulang.']);
});

it('menolak entitas yang belum aktif (enam entitas berantai)', function () {
    $this->post(route('impor.unggah', 'rumah'), ['berkas' => berkasCsvImpor("a\nb\n")])
        ->assertNotFound();
});

it('menolak pengguna tanpa kewenangan tambah pada entitas terkait', function () {
    $tanpaIzin = User::factory()->create(['role_id' => Role::factory()->create()->id_role]);

    $this->actingAs($tanpaIzin)
        ->post(route('impor.unggah', 'satuan'), ['berkas' => berkasCsvImpor("nama,simbol\nKarung,krg\n")])
        ->assertForbidden();
});
