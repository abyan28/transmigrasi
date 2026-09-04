<?php

/*
 * Task 4.3 + 4.4 -- inventaris dan fasilitas SP.
 *
 * Disatukan sebab strukturnya sama persis; yang membedakan hanya koordinat dan
 * cakupan lintas SP milik fasilitas.
 */

use App\Models\Berkas;
use App\Models\FasilitasSp;
use App\Models\InventarisSp;
use App\Models\User;
use Database\Seeders\AsetSpSeeder;
use Database\Seeders\BerkasSeeder;
use Database\Seeders\DaftarPilihanSeeder;
use Database\Seeders\InfrastrukturSeeder;
use Database\Seeders\KawasanSeeder;
use Database\Seeders\RumahSeeder;
use Database\Seeders\SpSeeder;
use Database\Seeders\TransmigranSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/DatabaseHelpers.php';

beforeEach(function () {
    $petugas = User::factory()->create();
    $petugas->semuaIzin = true;
    $this->actingAs($petugas);
    $this->seed(WilayahSeeder::class);
    $this->seed(KawasanSeeder::class);
    $this->seed(SpSeeder::class);
    $this->seed(DaftarPilihanSeeder::class);
    $this->seed(AsetSpSeeder::class);
    // `BerkasSeeder` menanam pivot SELURUH modul yang siap (infrastruktur,
    // transmigran, rumah) -- induknya wajib ada lebih dulu atau FK-nya gagal.
    $this->seed(InfrastrukturSeeder::class);
    $this->seed(TransmigranSeeder::class);
    $this->seed(RumahSeeder::class);
    $this->seed(BerkasSeeder::class);
});

it('menanam inventaris dan fasilitas beserta cakupannya', function () {
    expect(InventarisSp::count())->toBe(5)
        ->and(FasilitasSp::count())->toBeGreaterThan(0);

    // SP pangkal WAJIB ikut pada cakupan fasilitasnya.
    foreach (FasilitasSp::with('cakupan')->get() as $f) {
        expect($f->cakupan->pluck('id_satuan_permukiman'))
            ->toContain($f->satuan_permukiman_id);
    }
});

it('menyimpan rincian kondisi sebagai histogram JSON', function () {
    // Histogram per JENIS barang, bukan per unit: kursi ke-3 tetap tak dapat
    // dibedakan dari kursi ke-7.
    $kursi = InventarisSp::where('nama_barang', 'KURSI PLASTIK')->first();

    expect($kursi->rincian_kondisi)->toBeArray()
        ->and($kursi->rincian_kondisi['Baik'])->toBe(43)
        ->and(array_sum($kursi->rincian_kondisi))->toBe($kursi->jumlah);
});

it('menyimpan inventaris baru beserta fotonya', function () {
    Storage::fake('local');

    $this->post(route('inventaris.simpan'), [
        'satuan_permukiman_id' => 1,
        'jenis_inventaris' => 'Perabotan',
        'nama_barang' => 'RAK BUKU UJI',
        'jumlah' => 5,
        'satuan_barang' => 'unit',
        'tahun_perolehan' => 2020,
        'sumber_dana' => 'APBN',
        'status_penyerahan' => 'Sudah Diserahkan',
        'kondisi' => 'Baik',
        'foto' => [UploadedFile::fake()->image('rak.jpg')],
    ])->assertRedirect(route('sp.inventaris'));

    $baru = InventarisSp::where('nama_barang', 'RAK BUKU UJI')->first();

    expect($baru)->not->toBeNull()
        ->and($baru->berkas)->toHaveCount(1)
        ->and($baru->berkas->first()->pivot->peran)->toBe('foto');
});

it('menolak kolom REF yang tidak ada pada daftar pilihan', function () {
    // Kolom REF disimpan TEKS dan dicocokkan ke tabel `daftar_pilihan`, bukan enum
    // PHP -- tetapi nilai karangan tetap wajib ditolak.
    $this->post(route('inventaris.simpan'), [
        'satuan_permukiman_id' => 1,
        'nama_barang' => 'BARANG SESAT',
        'jumlah' => 1,
        'kondisi' => 'Sangat Baik Sekali',
    ])->assertSessionHasErrors('kondisi');

    expect(InventarisSp::where('nama_barang', 'BARANG SESAT')->exists())->toBeFalse();
});

it('menolak nilai daftar pilihan yang sudah dinonaktifkan', function () {
    // Nilai nonaktif tetap terbaca pada data lama, tetapi tidak boleh dipakai
    // pada data baru.
    $this->post(route('inventaris.simpan'), [
        'satuan_permukiman_id' => 1,
        'nama_barang' => 'BARANG NONAKTIF',
        'jumlah' => 1,
        'sumber_dana' => 'Lembaga Swadaya Masyarakat',
    ])->assertSessionHasErrors('sumber_dana');
});

it('menyertakan SP pangkal pada cakupan walau tak disebut form', function () {
    // Fasilitas yang tak melayani SP tempatnya berdiri tidak masuk akal,
    // sehingga pangkalnya disertakan apa pun isian formnya.
    $this->post(route('fasilitas.simpan'), [
        'satuan_permukiman_id' => 2,
        'jenis_fasilitas' => 'Kesehatan',
        'nama_fasilitas' => 'POSKESDES UJI',
        'jumlah' => 1,
        'kondisi' => 'Baik',
        'status_penyerahan' => 'Sudah Diserahkan',
        'satuan_permukiman_ids_lain' => [3],
    ])->assertRedirect(route('sp.fasilitas'));

    $baru = FasilitasSp::where('nama_fasilitas', 'POSKESDES UJI')->first();

    expect($baru->cakupan->pluck('id_satuan_permukiman')->sort()->values()->all())
        ->toBe([2, 3]);
});

it('menolak jenis fasilitas di luar enum skema', function () {
    $this->post(route('fasilitas.simpan'), [
        'satuan_permukiman_id' => 1,
        'jenis_fasilitas' => 'Bandar Antariksa',
        'nama_fasilitas' => 'FASILITAS SESAT',
        'jumlah' => 1,
        'status_penyerahan' => 'Sudah Diserahkan',
    ])->assertSessionHasErrors('jenis_fasilitas');
});

it('menghapus fasilitas beserta pivot tanpa membuang registry berkas', function () {
    $fasilitas = FasilitasSp::with('berkas')->whereHas('berkas')->first();

    if ($fasilitas === null) {
        $this->markTestSkipped('Data contoh belum punya fasilitas berberkas.');
    }

    $jumlahBerkas = Berkas::count();

    $this->delete(route('fasilitas.hapus', $fasilitas->id_fasilitas_sp))
        ->assertRedirect(route('sp.fasilitas'));

    expect(FasilitasSp::find($fasilitas->id_fasilitas_sp))->toBeNull()
        ->and(Berkas::count())->toBe($jumlahBerkas);
});
