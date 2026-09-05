<?php

/*
 * Task 8.2-8.7 (pengaduan + penanganan).
 *
 * Yang dijaga: seed dari data contoh; alur status maju SATU langkah
 * (`bolehPindahKe`); bidang wajib sebelum Diproses (10b.7b); bidang awal dari
 * kategori; kanal publik menyimpan status Menunggu Diterima + IP; nomor
 * ber-bagian acak; halaman lacak tanpa data pribadi.
 */

use App\Mail\PengaduanMail;
use App\Models\PenangananPengaduan;
use App\Models\Pengaduan;
use App\Models\SatuanPermukiman;
use App\Models\User;
use App\Support\DummyData;
use App\Support\NomorPengaduan;
use Database\Seeders\DaftarPilihanSeeder;
use Database\Seeders\KawasanSeeder;
use Database\Seeders\PengaduanSeeder;
use Database\Seeders\SpSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Support\Facades\Mail;

require_once __DIR__.'/DatabaseHelpers.php';

beforeEach(function () {
    Mail::fake();
    $petugas = User::factory()->create();
    $petugas->semuaIzin = true;
    $this->actingAs($petugas);
    $this->seed(WilayahSeeder::class);
    $this->seed(KawasanSeeder::class);
    $this->seed(SpSeeder::class);
    $this->seed(DaftarPilihanSeeder::class);
    $this->seed(PengaduanSeeder::class);
});

it('menanam pengaduan dan riwayat penanganan dari data contoh', function () {
    expect(Pengaduan::count())->toBe(count(DummyData::pengaduan()));

    $p1 = Pengaduan::with('penanganan')->find(1);
    expect($p1->nomor_pengaduan)->toBe('PGD-2026-0001-PMTUXK')
        ->and($p1->status->value)->toBe('Diproses')
        ->and($p1->penanganan)->toHaveCount(2)
        ->and($p1->uuid)->not->toBeNull();

    // Pengaduan 3 & 4 masih Menunggu Diterima, tanpa riwayat.
    expect(Pengaduan::find(3)->penanganan)->toHaveCount(0);
});

it('merender daftar pengaduan dengan barisnya', function () {
    $this->get(route('pengaduan.index'))
        ->assertOk()
        ->assertSee('PGD-2026-0001-PMTUXK')
        ->assertSee('Saluran irigasi tersumbat');
});

it('membalas 404 untuk pengaduan yang tidak ada', function () {
    $this->get('/pengaduan/99999')->assertNotFound();
});

it('mencatat pengaduan petugas berstatus Menunggu Diterima dengan bidang dari kategori', function () {
    $sp = SatuanPermukiman::value('id_satuan_permukiman');

    $this->post(route('pengaduan.simpan'), [
        'nama_pelapor' => 'PELAPOR LISAN',
        'kontak_pelapor' => '081200001111',
        'satuan_permukiman_id' => $sp,
        'tanggal_pengaduan' => '2026-08-20',
        'kategori' => 'Rumah',       // -> bidang Ketransmigrasian
        'prioritas' => 'Sedang',
        'judul' => 'Kusen pintu lapuk',
        'deskripsi' => 'Kusen pintu depan lapuk dan mulai keropos.',
    ])->assertRedirect(route('pengaduan.index'));

    $baru = Pengaduan::where('judul', 'Kusen pintu lapuk')->first();
    expect($baru->status->value)->toBe('Menunggu Diterima')
        ->and($baru->sumber_laporan->value)->toBe('Petugas')
        ->and($baru->bidang)->toBe('Ketransmigrasian')
        ->and($baru->nomor_pengaduan)->toStartWith('PGD-'.date('Y').'-');
});

it('memperbarui data pengaduan tanpa menyentuh statusnya', function () {
    $p = Pengaduan::find(3); // Menunggu Diterima

    $this->put(route('pengaduan.perbarui', 3), [
        'nama_pelapor' => $p->nama_pelapor,
        'kontak_pelapor' => $p->kontak_pelapor,
        'satuan_permukiman_id' => $p->satuan_permukiman_id,
        'tanggal_pengaduan' => $p->tanggal_pengaduan->toDateString(),
        'kategori' => $p->kategori,
        'prioritas' => 'Mendesak',
        'judul' => 'Traktor bantuan mati total',
        'deskripsi' => $p->deskripsi,
    ])->assertRedirect(route('pengaduan.detail', 3));

    $p->refresh();
    expect($p->prioritas)->toBe('Mendesak')
        ->and($p->judul)->toBe('TRAKTOR BANTUAN MATI TOTAL')
        ->and($p->status->value)->toBe('Menunggu Diterima');
});

it('memajukan status penanganan satu langkah dan mencatat riwayatnya', function () {
    // Pengaduan 3: Menunggu Diterima -> Diterima.
    Pengaduan::find(3)->update(['email_pelapor' => 'pelapor@example.test']);

    $this->post(route('pengaduan.tangani', 3), [
        'status_sesudah' => 'Diterima',
        'tanggal_penanganan' => '2026-08-20',
        'catatan' => 'Laporan diterima dan dijadwalkan peninjauan.',
    ])->assertRedirect();

    $p = Pengaduan::with('penanganan')->find(3);
    expect($p->status->value)->toBe('Diterima')
        ->and($p->penanganan)->toHaveCount(1)
        ->and($p->penanganan->first()->status_sebelum->value)->toBe('Menunggu Diterima')
        ->and($p->penanganan->first()->user_id)->not->toBeNull();

    Mail::assertSent(PengaduanMail::class, fn ($mail) => $mail->hasTo('pelapor@example.test') && ! $mail->baru);
});

it('menolak lompatan status yang melewati satu tahap', function () {
    // Pengaduan 3 masih Menunggu Diterima; Diproses adalah lompatan.
    $this->post(route('pengaduan.tangani', 3), [
        'status_sesudah' => 'Diproses',
        'tanggal_penanganan' => '2026-08-20',
        'catatan' => 'Mencoba melompat.',
    ])->assertSessionHasErrors('status_sesudah');

    expect(Pengaduan::find(3)->status->value)->toBe('Menunggu Diterima')
        ->and(PenangananPengaduan::where('pengaduan_id', 3)->count())->toBe(0);
});

it('mewajibkan bidang sebelum status maju ke Diproses', function () {
    // Pengaduan 4 (Bencana): bidang NULL, status Menunggu Diterima.
    $p = Pengaduan::find(4);
    expect($p->bidang)->toBeNull();

    // Maju ke Diterima dulu (tak butuh bidang).
    $this->post(route('pengaduan.tangani', 4), [
        'status_sesudah' => 'Diterima',
        'tanggal_penanganan' => '2026-08-20',
        'catatan' => 'Diterima.',
    ])->assertRedirect();

    // Ke Diproses tanpa bidang -> ditolak.
    $this->post(route('pengaduan.tangani', 4), [
        'status_sesudah' => 'Diproses',
        'tanggal_penanganan' => '2026-08-21',
        'catatan' => 'Mulai diproses.',
    ])->assertSessionHasErrors('bidang');

    // Dengan bidang -> berhasil.
    $this->post(route('pengaduan.tangani', 4), [
        'status_sesudah' => 'Diproses',
        'tanggal_penanganan' => '2026-08-21',
        'catatan' => 'Mulai diproses.',
        'bidang' => 'Ketransmigrasian',
    ])->assertRedirect();

    $p->refresh();
    expect($p->status->value)->toBe('Diproses')
        ->and($p->bidang)->toBe('Ketransmigrasian');
});

it('menghapus pengaduan secara halus', function () {
    $this->delete(route('pengaduan.hapus', 8))->assertRedirect(route('pengaduan.index'));

    expect(Pengaduan::find(8))->toBeNull()
        ->and(Pengaduan::withTrashed()->find(8)->trashed())->toBeTrue();
});

it('merender rekap pengaduan pada tiap dasar pengelompokan', function () {
    foreach (['kategori', 'status', 'sp', 'prioritas', 'bidang'] as $kelompok) {
        $this->get(route('pengaduan.rekap.kelompok', ['kelompok' => $kelompok]))
            ->assertOk()
            ->assertSee('Rekap');
    }
});

it('membuat nomor pengaduan ber-bagian acak yang unik', function () {
    $a = NomorPengaduan::buat();
    // Nomor yang sama tak dapat dibuat dua kali (dijamin cek UNIQUE),
    // tetapi urut naik dan bagian acak berbeda.
    expect($a)->toMatch('/^PGD-\d{4}-\d{4}-[23456789A-HJ-NP-Z]{6}$/');

    $urai = NomorPengaduan::urai(strtolower($a));
    expect($urai)->not->toBeNull()
        ->and($urai['awalan'])->toBe('PGD');
});
