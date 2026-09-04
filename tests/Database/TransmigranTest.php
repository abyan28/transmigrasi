<?php

/*
 * Task 5.1 -- peralihan transmigran + anggota_keluarga ke Eloquent (jalur BACA).
 * Task 5.2 -- CRUD + unggah KTP/KK/SK + catat peristiwa + suksesi kepala keluarga.
 */

use App\Enums\HubunganAnggotaKeluarga;
use App\Enums\StatusAnggotaKeluarga;
use App\Models\AnggotaKeluarga;
use App\Models\RiwayatKepalaKeluarga;
use App\Models\Transmigran;
use App\Models\User;
use App\Support\DummyData;
use Database\Seeders\KawasanSeeder;
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
    $this->seed(TransmigranSeeder::class);
});

it('menanam seluruh transmigran dan anggota keluarga dari data contoh', function () {
    expect(Transmigran::count())->toBe(count(DummyData::transmigran()))
        ->and(AnggotaKeluarga::count())->toBe(count(DummyData::anggotaKeluarga()));

    $yohanes = Transmigran::where('nik', '5321011505800001')->first();
    expect($yohanes)->not->toBeNull()
        ->and($yohanes->uuid)->not->toBeNull()
        ->and($yohanes->satuanPermukiman->nama)->toBe('SP Kapitan Meo');
});

it('mempertahankan uuid saat ditanam ulang', function () {
    $sebelum = Transmigran::orderBy('id_transmigran')->pluck('uuid', 'id_transmigran');

    $this->seed(TransmigranSeeder::class);

    expect(Transmigran::orderBy('id_transmigran')->pluck('uuid', 'id_transmigran')->all())
        ->toBe($sebelum->all());
});

it('merender daftar transmigran dari basis data', function () {
    $respons = $this->get(route('transmigran.index'))->assertOk();

    foreach (DummyData::transmigran() as $t) {
        $respons->assertSee($t['nama_kepala_keluarga'])->assertSee($t['nik']);
    }
});

it('menyaring daftar transmigran menurut satuan permukiman', function () {
    $this->get(route('transmigran.index', ['sp' => 2]))
        ->assertOk()
        ->assertSee('PETRUS NAHAK')
        ->assertDontSee('YOHANES BERE');
});

it('mencari transmigran memakai nomor KK', function () {
    $this->get(route('transmigran.index', ['cari' => '5321010102150001']))
        ->assertOk()
        ->assertSee('YOHANES BERE')
        ->assertDontSee('MARIA DA COSTA');
});

it('merender rincian transmigran beserta anggota keluarganya', function () {
    $petrus = Transmigran::where('nama_kepala_keluarga', 'PETRUS NAHAK')->first();

    $this->get(route('transmigran.detail', $petrus->id_transmigran))
        ->assertOk()
        ->assertSee('PETRUS NAHAK')
        ->assertSee($petrus->no_kk)
        ->assertSee('YOVITA NAHAK SERAN')
        ->assertSee('ROSALIA SERAN');
});

it('membalas 404 untuk transmigran yang tidak ada', function () {
    $this->get('/transmigran/99999')->assertNotFound();
});

it('menurunkan jumlah anggota keluarga dari cacah baris aktif', function () {
    foreach (DummyData::transmigran() as $contoh) {
        $kk = Transmigran::with('anggotaKeluarga')->find($contoh['id_transmigran']);

        $aktif = $kk->anggotaKeluarga
            ->filter(fn ($a) => $a->status === StatusAnggotaKeluarga::Aktif)
            ->count();

        expect(1 + $aktif)->toBe($contoh['jumlah_anggota_keluarga']);
    }
});

/*
|--------------------------------------------------------------------------
| Task 5.2 -- CRUD + unggah berkas + peristiwa + suksesi
|--------------------------------------------------------------------------
*/

function dataTransmigranBaru(array $ganti = []): array
{
    return array_merge([
        'nama_kepala_keluarga' => 'YUSTUS SERAN',
        'nik' => '5321010101900777',
        'no_kk' => '5321010101900778',
        'jenis_kelamin' => 'Laki-laki',
        'agama' => 'Katolik',
        'pekerjaan_kepala_keluarga' => 'PETANI',
        'satuan_permukiman_id' => 1,
        'tahun_kedatangan' => 2018,
        'status_tinggal' => 'Aktif',
        '_anggota_disunting' => '1',
    ], $ganti);
}

it('menyimpan transmigran baru beserta anggota keluarganya', function () {
    $this->post(route('transmigran.simpan'), dataTransmigranBaru([
        'anggota_keluarga' => [
            ['hubungan' => 'Istri', 'nama_lengkap' => 'MARGARETH SERAN', 'kegiatan' => 'Tidak Bekerja', 'pendidikan_terakhir' => 'SD'],
            ['hubungan' => 'Anak', 'nama_lengkap' => 'PETRUS SERAN', 'kegiatan' => 'Belum Sekolah'],
        ],
    ]))->assertRedirect(route('transmigran.index'));

    $kk = Transmigran::where('nik', '5321010101900777')->first();

    expect($kk)->not->toBeNull()
        ->and($kk->uuid)->not->toBeNull()
        ->and($kk->anggotaKeluarga)->toHaveCount(2)
        ->and($kk->anggotaKeluarga->pluck('nama_lengkap')->all())
        ->toContain('MARGARETH SERAN', 'PETRUS SERAN');
});

it('mengunggah KTP, KK, dan SK sebagai tiga peran berkas terpisah', function () {
    Storage::fake('local');

    $this->post(route('transmigran.simpan'), dataTransmigranBaru([
        'ktp' => [UploadedFile::fake()->create('ktp.pdf', 40, 'application/pdf')],
        'kk' => [UploadedFile::fake()->create('kk.pdf', 40, 'application/pdf')],
        'sk' => [UploadedFile::fake()->create('sk.pdf', 40, 'application/pdf')],
    ]))->assertRedirect(route('transmigran.index'));

    $kk = Transmigran::where('nik', '5321010101900777')->first();

    expect($kk->berkas->pluck('pivot.peran')->sort()->values()->all())
        ->toBe(['kk', 'ktp', 'sk']);

    foreach ($kk->berkas as $b) {
        Storage::disk('local')->assertExists($b->path);
        expect($b->path)->not->toContain('public');
    }
});

it('menolak NIK yang sudah dipakai transmigran lain', function () {
    $this->post(route('transmigran.simpan'), dataTransmigranBaru([
        'nik' => '5321011505800001', // YOHANES BERE
    ]))->assertSessionHasErrors('nik');
});

it('memperbarui transmigran dan menyinkronkan daftar anggota keluarga', function () {
    $petrus = Transmigran::with('anggotaKeluarga')->where('nama_kepala_keluarga', 'PETRUS NAHAK')->first();
    $istri = $petrus->anggotaKeluarga->firstWhere('hubungan', HubunganAnggotaKeluarga::Istri);
    $anak = $petrus->anggotaKeluarga->firstWhere('nama_lengkap', 'AGUSTINUS NAHAK');

    $this->put(route('transmigran.perbarui', $petrus->id_transmigran), [
        'nama_kepala_keluarga' => $petrus->nama_kepala_keluarga,
        'nik' => $petrus->nik,
        'no_kk' => $petrus->no_kk,
        'pekerjaan_kepala_keluarga' => 'PEKEBUN',
        'satuan_permukiman_id' => $petrus->satuan_permukiman_id,
        'tahun_kedatangan' => $petrus->tahun_kedatangan,
        'status_tinggal' => 'Aktif',
        '_anggota_disunting' => '1',
        'anggota_keluarga' => [
            // istri dipertahankan (ber-id), namanya dibetulkan
            ['id' => $istri->id_anggota_keluarga, 'hubungan' => 'Istri', 'nama_lengkap' => 'YOVITA SERAN', 'kegiatan' => 'Tidak Bekerja', 'pendidikan_terakhir' => 'SD'],
            // anggota baru tanpa id
            ['hubungan' => 'Anak', 'nama_lengkap' => 'BENEDIKTA NAHAK', 'kegiatan' => 'Belum Sekolah'],
        ],
    ])->assertRedirect(route('transmigran.detail', $petrus->id_transmigran));

    expect($petrus->fresh()->pekerjaan_kepala_keluarga)->toBe('PEKEBUN');
    expect($istri->fresh()->nama_lengkap)->toBe('YOVITA SERAN');
    // anak aktif yang tak ikut terkirim -> soft delete
    expect($anak->fresh()->trashed())->toBeTrue();
    expect(AnggotaKeluarga::where('transmigran_id', $petrus->id_transmigran)->where('nama_lengkap', 'BENEDIKTA NAHAK')->exists())->toBeTrue();
});

it('tidak menyentuh anggota keluarga saat penanda sunting tidak ada', function () {
    $petrus = Transmigran::with('anggotaKeluarga')->where('nama_kepala_keluarga', 'PETRUS NAHAK')->first();
    $sebelum = $petrus->anggotaKeluarga->count();

    $this->put(route('transmigran.perbarui', $petrus->id_transmigran), [
        'nama_kepala_keluarga' => $petrus->nama_kepala_keluarga,
        'nik' => $petrus->nik,
        'no_kk' => $petrus->no_kk,
        'pekerjaan_kepala_keluarga' => 'NELAYAN',
        'satuan_permukiman_id' => $petrus->satuan_permukiman_id,
        'tahun_kedatangan' => $petrus->tahun_kedatangan,
        'status_tinggal' => 'Aktif',
    ])->assertRedirect(route('transmigran.detail', $petrus->id_transmigran));

    expect($petrus->fresh()->pekerjaan_kepala_keluarga)->toBe('NELAYAN')
        ->and($petrus->fresh()->anggotaKeluarga()->count())->toBe($sebelum);
});

it('menghapus transmigran secara halus', function () {
    $id = Transmigran::where('nama_kepala_keluarga', 'YULITA HOAR')->value('id_transmigran');

    $this->delete(route('transmigran.hapus', $id))->assertRedirect(route('transmigran.index'));

    expect(Transmigran::find($id))->toBeNull()
        ->and(Transmigran::withTrashed()->find($id)->trashed())->toBeTrue();
});

it('mencatat peristiwa pada anggota keluarga tanpa menghapus barisnya', function () {
    $kk = Transmigran::where('nama_kepala_keluarga', 'PETRUS NAHAK')->first();
    $anak = AnggotaKeluarga::where('transmigran_id', $kk->id_transmigran)
        ->where('nama_lengkap', 'AGUSTINUS NAHAK')->first();

    $this->post(route('transmigran.anggota.catat-peristiwa', ['id' => $kk->id_transmigran, 'anggota' => $anak->id_anggota_keluarga]), [
        'status' => 'Pindah',
        'tanggal_peristiwa' => '2026-08-01',
        'keterangan_peristiwa' => 'Merantau ke Kupang.',
    ])->assertRedirect('/transmigran/'.$kk->id_transmigran.'?tab=keluarga');

    $anak->refresh();
    expect($anak->status)->toBe(StatusAnggotaKeluarga::Pindah)
        ->and($anak->tanggal_peristiwa->toDateString())->toBe('2026-08-01')
        ->and($anak->trashed())->toBeFalse();
});

it('menolak pencatatan peristiwa tanpa status', function () {
    $kk = Transmigran::where('nama_kepala_keluarga', 'PETRUS NAHAK')->first();
    $anak = AnggotaKeluarga::where('transmigran_id', $kk->id_transmigran)->where('status', 'Aktif')->first();

    $this->post(route('transmigran.anggota.catat-peristiwa', ['id' => $kk->id_transmigran, 'anggota' => $anak->id_anggota_keluarga]))
        ->assertSessionHasErrors('status');
});

it('menjalankan suksesi kepala keluarga sebagai satu transaksi', function () {
    // PETRUS NAHAK tidak menjabat ketua poktan (DummyData), jadi nasib jabatan
    // tidak diminta.
    $kk = Transmigran::with('anggotaKeluarga')->where('nama_kepala_keluarga', 'PETRUS NAHAK')->first();
    $istri = $kk->anggotaKeluarga->firstWhere('hubungan', HubunganAnggotaKeluarga::Istri);
    $nikLama = $kk->nik;

    $this->post(route('transmigran.ganti-kepala-keluarga', $kk->id_transmigran), [
        'pengganti_anggota_keluarga_id' => $istri->id_anggota_keluarga,
        'no_kk_baru' => '5321010101990111',
        'tanggal_pergantian' => '2026-05-01',
        'alasan' => 'Meninggal',
        'keterangan' => 'Akta kematian sudah diserahkan.',
    ])->assertRedirect(route('transmigran.detail', ['id' => $kk->id_transmigran, 'tab' => 'riwayat-kk']));

    $kk->refresh();
    expect($kk->nama_kepala_keluarga)->toBe($istri->nama_lengkap)
        ->and($kk->nik)->toBe((string) $istri->nik)
        ->and($kk->no_kk)->toBe('5321010101990111');

    expect($istri->fresh()->trashed())->toBeTrue();

    $riwayat = RiwayatKepalaKeluarga::where('transmigran_id', $kk->id_transmigran)
        ->orderByDesc('id_riwayat_kepala_keluarga')->first();
    expect($riwayat->nik_lama)->toBe($nikLama)
        ->and($riwayat->nama_baru)->toBe($istri->nama_lengkap)
        ->and($riwayat->hubungan_pengganti->value)->toBe('Istri');
});

it('menuntut nasib jabatan ketua poktan bila keluarga menjabat ketua', function () {
    // YOHANES BERE (id 1) menjabat ketua POKTAN MEKAR JAYA pada data contoh.
    $kk = Transmigran::with('anggotaKeluarga')->find(1);
    $istri = $kk->anggotaKeluarga->firstWhere('hubungan', HubunganAnggotaKeluarga::Istri);

    $this->post(route('transmigran.ganti-kepala-keluarga', 1), [
        'pengganti_anggota_keluarga_id' => $istri->id_anggota_keluarga,
        'no_kk_baru' => $kk->no_kk,
        'tanggal_pergantian' => '2026-05-01',
        'alasan' => 'Meninggal',
    ])->assertSessionHasErrors('nasib_ketua_poktan');
});
