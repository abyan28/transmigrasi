<?php

/*
 * Task 8.3 (kanal pengaduan publik tanpa login).
 *
 * Yang dijaga: kirim tanpa akun -> status Menunggu Diterima + IP tercatat +
 * bidang awal dari kategori + nomor dibuat sistem; validasi menolak isian
 * kurang; halaman lacak hanya status/tanggal/catatan, tak pernah data pribadi.
 */

use App\Mail\PengaduanMail;
use App\Models\Pengaduan;
use App\Models\SatuanPermukiman;
use Database\Seeders\DaftarPilihanSeeder;
use Database\Seeders\KawasanSeeder;
use Database\Seeders\PengaduanSeeder;
use Database\Seeders\SpSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Support\Facades\Mail;

require_once __DIR__.'/DatabaseHelpers.php';

beforeEach(function () {
    Mail::fake();
    // TANPA actingAs: kanal publik.
    $this->seed(WilayahSeeder::class);
    $this->seed(KawasanSeeder::class);
    $this->seed(SpSeeder::class);
    $this->seed(DaftarPilihanSeeder::class);
    $this->seed(PengaduanSeeder::class);
});

it('menerima pengaduan warga tanpa login dan mencatat IP serta bidang awal', function () {
    $sp = SatuanPermukiman::value('id_satuan_permukiman');

    $this->post(route('pengaduan-warga.kirim'), [
        'nama_pelapor' => 'IBU LUSIA',
        'kontak_pelapor' => '081311112222',
        'email_pelapor' => 'lusia@contoh.com',
        'satuan_permukiman_id' => $sp,
        'kategori' => 'Kelompok Tani',   // -> bidang Pertanian
        'tanggal_pengaduan' => '2026-08-22',
        'judul' => 'Bantuan pupuk belum dibagikan',
        'deskripsi' => 'Sebagian anggota kelompok belum menerima pupuk bantuan.',
    ])->assertRedirect()->assertSessionHas('nomor_pengaduan');

    $baru = Pengaduan::where('judul', 'Bantuan pupuk belum dibagikan')->first();
    expect($baru->sumber_laporan->value)->toBe('Publik')
        ->and($baru->status->value)->toBe('Menunggu Diterima')
        ->and($baru->user_id)->toBeNull()
        ->and($baru->ip_pelapor)->not->toBeNull()
        ->and($baru->bidang)->toBe('Pertanian')
        ->and($baru->prioritas)->toBe('Sedang');

    Mail::assertQueued(PengaduanMail::class, fn ($mail) => $mail->baru);
});

it('membiarkan bidang kosong untuk kategori netral', function () {
    $sp = SatuanPermukiman::value('id_satuan_permukiman');

    $this->post(route('pengaduan-warga.kirim'), [
        'nama_pelapor' => 'BAPAK YOHANIS',
        'kontak_pelapor' => '081399998888',
        'satuan_permukiman_id' => $sp,
        'kategori' => 'Bencana',   // netral -> bidang kosong
        'tanggal_pengaduan' => '2026-08-22',
        'judul' => 'Longsor menutup jalan',
        'deskripsi' => 'Longsor kecil menutup jalan produksi menuju lahan.',
    ])->assertRedirect();

    expect(Pengaduan::where('judul', 'Longsor menutup jalan')->first()->bidang)->toBeNull();
});

it('menolak pengiriman tanpa isian wajib', function () {
    $this->post(route('pengaduan-warga.kirim'), [
        'nama_pelapor' => 'TANPA ISI',
    ])->assertSessionHasErrors(['kontak_pelapor', 'satuan_permukiman_id', 'kategori', 'judul', 'deskripsi']);

    expect(Pengaduan::where('nama_pelapor', 'TANPA ISI')->exists())->toBeFalse();
});

it('menampilkan status dan catatan penanganan pada halaman lacak tanpa data pribadi', function () {
    // PGD-2026-0005 sudah Selesai dengan tiga langkah penanganan.
    $this->get(route('lacak-pengaduan.nomor', ['nomor' => 'PGD-2026-0005-96RY4X']))
        ->assertOk()
        ->assertSee('Selesai')
        ->assertSee('Pendampingan penyemprotan selesai, kondisi tanaman membaik. Petani diberi panduan pengendalian hama.')
        // Tak pernah menampilkan nama/kontak pelapor.
        ->assertDontSee('GABRIEL LEKI')
        ->assertDontSee('081234567807');
});

it('menerima nomor lacak tanpa peduli huruf besar-kecil', function () {
    $this->get(route('lacak-pengaduan', ['nomor' => 'pgd-2026-0001-pmtuxk']))
        ->assertOk()
        ->assertSee('Saluran irigasi tersumbat');
});

it('menjelaskan keadaan saat nomor lacak tidak ditemukan', function () {
    $this->get(route('lacak-pengaduan', ['nomor' => 'PGD-2026-9999-XXXXXX']))
        ->assertOk()
        ->assertDontSee('Saluran irigasi tersumbat');
});
