<?php

/*
 * Task 9.6 -- Pengelolaan Konten Sistem (CMS).
 *
 * Yang dijaga: nilai bawaan berlaku sebelum diisi; tiap tab menyimpan
 * kuncinya sendiri; nama aplikasi jatuh ke config bila kosong; awalan nomor
 * pengaduan dari CMS dipakai `NomorPengaduan`; pengumuman aktif tampil di
 * dashboard; FAQ tampil di /panduan; narasi tampil di /tentang.
 */

use App\Mail\KodePemulihanSandiMail;
use App\Mail\KredensialAkunMail;
use App\Models\AuditLog;
use App\Models\Pengaturan;
use App\Models\User;
use App\Support\KontenSistem;
use App\Support\LaporanData;
use App\Support\NomorPengaduan;
use Database\Seeders\DaftarPilihanSeeder;
use Database\Seeders\KawasanSeeder;
use Database\Seeders\SpSeeder;
use Database\Seeders\WilayahSeeder;

require_once __DIR__.'/DatabaseHelpers.php';

beforeEach(function () {
    $petugas = User::factory()->create();
    $petugas->semuaIzin = true;
    $this->actingAs($petugas);
    $this->seed(WilayahSeeder::class);
    $this->seed(KawasanSeeder::class);
    $this->seed(SpSeeder::class);
    $this->seed(DaftarPilihanSeeder::class);
});

it('memakai nilai bawaan sebelum dinas mengisinya', function () {
    expect(Pengaturan::count())->toBe(0)
        ->and(KontenSistem::namaAplikasi())->toBe(config('app.name'))
        ->and(KontenSistem::faq())->toHaveCount(3)
        ->and(KontenSistem::pengumuman())->toBeNull()
        ->and(KontenSistem::awalanNomorPengaduan())->toBe('PGD');
});

it('merender halaman CMS dengan keenam tab', function () {
    $this->get(route('cms'))
        ->assertOk()
        ->assertSee('Pengelolaan Konten')
        ->assertSee('name="nama_app"', false)
        ->assertSee('name="kop_kementerian"', false)
        ->assertSee('name="latar_belakang"', false)
        ->assertSee('name="awalan_nomor"', false)
        ->assertSee('name="tab" value="surel"', false)
        ->assertSee('name="tab" value="pengumuman"', false);
});

it('menyimpan tab identitas dan mengubah nama aplikasi', function () {
    $this->put(route('cms.simpan'), [
        'tab' => 'identitas',
        'nama_app' => 'SIM Transmigrasi Malaka',
        'subjudul' => 'Kawasan Kobalima Timur',
        'email_bantuan' => 'bantuan@malakakab.go.id',
    ])->assertRedirect();

    expect(KontenSistem::namaAplikasi())->toBe('SIM Transmigrasi Malaka');

    // Tampil pada judul tab peramban.
    $this->get(route('beranda'))->assertSee('SIM Transmigrasi Malaka');
});

it('memakai identitas CMS pada halaman login', function () {
    $this->put(route('cms.simpan'), [
        'tab' => 'identitas',
        'nama_app' => 'SIM Login Uji',
        'subjudul' => 'Subjudul Login Uji',
    ])->assertRedirect();

    auth()->logout();
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('SIM Login Uji')
        ->assertSee('Subjudul Login Uji');
});

it('menampilkan identitas kontak dan footer CMS pada halaman internal dan publik', function () {
    $this->put(route('cms.simpan'), [
        'tab' => 'identitas',
        'nama_app' => 'SIM Kobalima',
        'subjudul' => 'Subjudul Kobalima Uji',
        'instansi_pusat' => 'Kementerian Uji',
        'instansi_daerah' => 'Dinas Uji',
        'email_bantuan' => 'bantuan@example.test',
        'telepon_bantuan' => '0389-111',
        'wa_bantuan' => '0812-111',
        'footer' => 'Footer resmi uji.',
    ])->assertRedirect();

    $this->get(route('beranda'))->assertSee('Footer resmi uji.');
    $this->get(route('pengaduan-warga'))
        ->assertSee('SIM Kobalima')
        ->assertSee('Subjudul Kobalima Uji')
        ->assertSee('Kementerian Uji')
        ->assertSee('Dinas Uji')
        ->assertSee('bantuan@example.test')
        ->assertSee('0389-111')
        ->assertSee('0812-111')
        ->assertSee('Footer resmi uji.');
});

it('menolak tab CMS yang tidak dikenal tanpa menyimpan data', function () {
    $this->put(route('cms.simpan'), [
        'tab' => 'tidak-ada',
        'nama_app' => 'Tidak Boleh Tersimpan',
        'subjudul' => 'Tidak Boleh Tersimpan',
    ])->assertSessionHasErrors('tab');

    expect(Pengaturan::count())->toBe(0);
});

it('mencatat satu audit perubahan CMS beserta pelaku dan kunci yang berubah', function () {
    $this->put(route('cms.simpan'), [
        'tab' => 'identitas',
        'nama_app' => 'SIM Audit',
        'subjudul' => 'Kawasan Audit',
    ])->assertRedirect();

    $audit = AuditLog::query()->where('nama_tabel', 'pengaturan')->sole();

    expect($audit->user_id)->toBe(auth()->id())
        ->and($audit->record_id)->toBe(0)
        ->and($audit->data_baru['identitas.nama_app'])->toBe('SIM Audit')
        ->and($audit->data_baru['identitas.subjudul'])->toBe('Kawasan Audit')
        ->and($audit->data_lama['identitas.nama_app'])->toBe(config('app.name'));
});

it('menggulung perubahan CMS bila pencatatan audit gagal', function () {
    $this->withoutExceptionHandling();
    AuditLog::creating(fn () => throw new RuntimeException('audit gagal'));

    expect(fn () => $this->put(route('cms.simpan'), [
        'tab' => 'identitas',
        'nama_app' => 'Tidak Boleh Tersimpan',
        'subjudul' => 'Tidak Boleh Tersimpan',
    ]))->toThrow(RuntimeException::class, 'audit gagal');

    expect(Pengaturan::count())->toBe(0);
});

it('mengingat pengaturan sekali per request dan menyegarkannya setelah simpan', function () {
    expect(KontenSistem::namaAplikasi())->toBe(config('app.name'));

    Pengaturan::create(['kunci' => 'identitas.nama_app', 'nilai' => 'Perubahan Langsung', 'tipe' => 'teks']);
    expect(KontenSistem::namaAplikasi())->toBe(config('app.name'));

    KontenSistem::simpan(['identitas.nama_app' => 'Perubahan Resmi']);
    expect(KontenSistem::namaAplikasi())->toBe('Perubahan Resmi');
});

it('menolak nama aplikasi dan subjudul kosong', function () {
    $this->put(route('cms.simpan'), ['tab' => 'identitas'])
        ->assertSessionHasErrors(['nama_app', 'subjudul']);
});

it('menyimpan kop laporan dan memakainya pada dokumen laporan', function () {
    $this->put(route('cms.simpan'), [
        'tab' => 'laporan',
        'kop_kementerian' => 'Kementerian Transmigrasi RI',
        'kop_pemerintah' => 'Pemerintah Kabupaten Malaka',
        'kop_dinas' => 'Dinas Transmigrasi Kabupaten Malaka',
        'kop_alamat' => 'Betun, Malaka, Nusa Tenggara Timur',
        'kop_kontak' => 'Telepon 0389-123',
        'tampilkan_ttd' => '1',
        'titimangsa_tempat' => 'Kota Uji',
        'ttd_jabatan' => 'Kepala Dinas',
        'ttd_nama' => 'Agustinus Nahak',
        'ttd_pangkat' => 'Pembina Uji',
        'ttd_nip' => '19750812 199903 1 004',
        'catatan_laporan' => ['transmigran' => 'Catatan editorial transmigran uji.'],
    ])->assertRedirect();

    expect(LaporanData::instansi()['dinas'])->toBe('Dinas Transmigrasi Kabupaten Malaka');

    $this->get(route('laporan.dokumen', 'transmigran'))
        ->assertOk()
        ->assertSee('Kota Uji')
        ->assertSee('Kepala Dinas')
        ->assertSee('Agustinus Nahak')
        ->assertSee('Pembina Uji')
        ->assertSee('19750812 199903 1 004')
        ->assertSee('Catatan editorial transmigran uji.');
});

it('tidak mengapitalkan teks konten CMS', function () {
    $this->put(route('cms.simpan'), [
        'tab' => 'informasi',
        'latar_belakang' => 'Kawasan Kobalima Timur memiliki potensi agroekologis.',
        'tim' => 'Tim pengembang uji.',
        'mitra' => 'Mitra kelembagaan uji.',
        'narahubung' => 'Narahubung uji.',
        'panduan' => [
            'peran' => 'Panduan peran uji.',
            'dashboard' => 'Panduan dashboard uji.',
            'wilayah' => 'Panduan wilayah uji.',
            'kependudukan' => 'Panduan kependudukan uji.',
            'pertanian' => 'Panduan pertanian uji.',
            'pengaduan' => 'Panduan pengaduan uji.',
            'laporan' => 'Panduan laporan uji.',
        ],
        'faq' => [['tanya' => 'Bagaimana cara masuk?', 'jawab' => 'Gunakan akun dari Admin.']],
    ])->assertRedirect();

    expect(KontenSistem::tentang())->toBe('Kawasan Kobalima Timur memiliki potensi agroekologis.')
        ->and(KontenSistem::faq())->toHaveCount(1)
        ->and(KontenSistem::faq()[0]['tanya'])->toBe('Bagaimana cara masuk?');

    $this->get(route('tentang'))
        ->assertSee('Kawasan Kobalima Timur memiliki potensi agroekologis.')
        ->assertSee('Tim pengembang uji.')
        ->assertSee('Mitra kelembagaan uji.')
        ->assertSee('Narahubung uji.');
    $this->get(route('panduan'))
        ->assertSee('Panduan peran uji.')
        ->assertSee('Panduan laporan uji.')
        ->assertSee('Bagaimana cara masuk?');
});

it('mengubah awalan nomor pengaduan yang dipakai NomorPengaduan', function () {
    $this->put(route('cms.simpan'), [
        'tab' => 'portal',
        'awalan_nomor' => 'lpr',
        'sambutan' => 'Sampaikan laporan Anda.',
    ])->assertRedirect();

    expect(KontenSistem::awalanNomorPengaduan())->toBe('LPR')
        ->and(NomorPengaduan::buat())->toStartWith('LPR-'.date('Y').'-');
});

it('menampilkan hotline CMS pada layanan pengaduan publik', function () {
    $this->put(route('cms.simpan'), [
        'tab' => 'portal',
        'awalan_nomor' => 'PGD',
        'hotline' => '0811-999-888',
        'alur' => 'Alur layanan uji.',
        'sla' => 'SLA layanan uji.',
        'pelacakan' => 'Petunjuk pelacakan uji.',
    ])->assertRedirect();

    $this->get(route('pengaduan-warga'))
        ->assertSee('Alur layanan uji.')
        ->assertSee('SLA layanan uji.');
    $this->get(route('lacak-pengaduan'))
        ->assertSee('0811-999-888')
        ->assertSee('Petunjuk pelacakan uji.');
});

it('menolak awalan nomor yang mengandung angka', function () {
    $this->put(route('cms.simpan'), ['tab' => 'portal', 'awalan_nomor' => 'PGD1'])
        ->assertSessionHasErrors('awalan_nomor');
});

it('menyimpan bahasa surel sistem', function () {
    $this->put(route('cms.simpan'), [
        'tab' => 'surel',
        'sapaan' => 'Yth. Bapak/Ibu',
        'penutup' => 'Hormat kami,',
        'nama_pengirim' => 'UPT Kobalima Timur',
        'catatan_kaki' => 'Pesan otomatis.',
    ])->assertRedirect();

    expect(KontenSistem::teks('surel.sapaan'))->toBe('Yth. Bapak/Ibu')
        ->and(KontenSistem::teks('surel.nama_pengirim'))->toBe('UPT Kobalima Timur');
});

it('merender surel formal dengan identitas CMS', function () {
    Pengaturan::create(['kunci' => 'surel.nama_pengirim', 'nilai' => 'UPT UJI', 'tipe' => 'teks']);

    expect((new KodePemulihanSandiMail('123456'))->render())
        ->toContain('Kementerian Transmigrasi')
        ->toContain('123456')
        ->toContain('UPT UJI')
        ->and((new KredensialAkunMail('Nara', 'nara@example.test', 'Rahasia123'))->render())
        ->toContain('nara@example.test')
        ->toContain('Rahasia123');
});

it('menampilkan banner pengumuman aktif pada dashboard', function () {
    $this->put(route('cms.simpan'), [
        'tab' => 'pengumuman',
        'aktif' => '1',
        'judul' => 'Distribusi Benih Jagung 2026',
        'tipe' => 'warning',
        'isi' => 'Distribusi dimulai pekan depan lewat kantor UPT.',
    ])->assertRedirect();

    $this->get(route('beranda'))
        ->assertOk()
        ->assertSee('Distribusi Benih Jagung 2026')
        ->assertSee('Distribusi dimulai pekan depan lewat kantor UPT.');
});

it('mewajibkan judul dan isi saat pengumuman diaktifkan', function () {
    $this->put(route('cms.simpan'), ['tab' => 'pengumuman', 'aktif' => '1', 'tipe' => 'info'])
        ->assertSessionHasErrors(['judul', 'isi']);
});
