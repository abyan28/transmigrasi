<?php

/*
 * Task 3.1 -- DOMAIN 9 (Infrastruktur & Pengaduan).
 *
 * Tabel: infrastruktur, infrastruktur_sp (pivot), pengaduan, penanganan_pengaduan
 * (+ pivot infrastruktur_berkas, pengaduan_berkas, penanganan_pengaduan_berkas).
 * `sim:banding-skema` menjaga kolom/indeks/FK.
 */

use App\Enums\StatusPengaduan;
use App\Enums\SumberLaporan;
use App\Models\Berkas;
use App\Models\Infrastruktur;
use App\Models\PenangananPengaduan;
use App\Models\Pengaduan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

require_once __DIR__.'/DatabaseHelpers.php';

function buatPengaduan(array $atribut = []): Pengaduan
{
    $sp = buatSp();

    return Pengaduan::create(array_merge([
        'uuid' => (string) Str::uuid(),
        'nama_pelapor' => 'Warga SP '.Str::random(4),
        'kontak_pelapor' => '0812'.random_int(1000000, 9999999),
        'sumber_laporan' => SumberLaporan::Publik->value,
        'satuan_permukiman_id' => $sp->id_satuan_permukiman,
        'nomor_pengaduan' => 'PGD-2026-'.random_int(1000, 9999).'-'.Str::upper(Str::random(6)),
        'tanggal_pengaduan' => '2026-08-15',
        'kategori' => 'Jalan Rusak',
        'judul' => 'Jembatan penghubung ambruk',
        'deskripsi' => 'Jembatan kayu di batas SP tidak bisa dilewati kendaraan.',
        'status' => StatusPengaduan::MenungguDiterima->value,
        'prioritas' => 'Tinggi',
    ], $atribut));
}

it('membuat keempat tabel + tiga pivot batch ini', function () {
    foreach ([
        'infrastruktur', 'infrastruktur_sp', 'pengaduan', 'penanganan_pengaduan',
        'infrastruktur_berkas', 'pengaduan_berkas', 'penanganan_pengaduan_berkas',
    ] as $tabel) {
        expect(Schema::hasTable($tabel))->toBeTrue("tabel {$tabel} tidak dibuat");
    }
});

it('memakai PK tunggal dan uuid route key untuk pengaduan', function () {
    expect((new Infrastruktur)->getKeyName())->toBe('id_infrastruktur')
        ->and((new Pengaduan)->getKeyName())->toBe('id_pengaduan')
        ->and((new Pengaduan)->getRouteKeyName())->toBe('uuid')
        ->and((new PenangananPengaduan)->getKeyName())->toBe('id_penanganan_pengaduan');
});

it('mencakup layanan infrastruktur lintas SP lewat pivot infrastruktur_sp', function () {
    $spPangkal = buatSp();
    $spTetangga = buatSp();
    $infra = Infrastruktur::create([
        'satuan_permukiman_id' => $spPangkal->id_satuan_permukiman,
        'nama' => 'Jaringan Air Bersih', 'jenis' => 'Air', 'kondisi' => 'Baik',
    ]);

    $infra->cakupan()->attach([$spPangkal->id_satuan_permukiman, $spTetangga->id_satuan_permukiman]);

    expect($infra->cakupan()->count())->toBe(2)
        ->and($spPangkal->infrastruktur->pluck('id_infrastruktur'))->toContain($infra->id_infrastruktur);

    // pasangan sama tak boleh dobel.
    expect(fn () => $infra->cakupan()->attach($spPangkal->id_satuan_permukiman))->toThrow(QueryException::class);

    // SP pangkal tidak dapat dihapus (RESTRICT) selagi memiliki infrastruktur.
    expect(fn () => $spPangkal->forceDelete())->toThrow(QueryException::class);
});

it('meng-cast ENUM sumber_laporan dan status pengaduan', function () {
    $pengaduan = buatPengaduan();

    expect($pengaduan->sumber_laporan)->toBe(SumberLaporan::Publik)
        ->and($pengaduan->status)->toBe(StatusPengaduan::MenungguDiterima)
        ->and($pengaduan->tanggal_pengaduan->format('Y-m-d'))->toBe('2026-08-15');
});

it('menegakkan UNIQUE uuid dan nomor_pengaduan', function () {
    $p = buatPengaduan();

    expect(fn () => buatPengaduan(['uuid' => $p->uuid]))->toThrow(QueryException::class);
    expect(fn () => buatPengaduan(['nomor_pengaduan' => $p->nomor_pengaduan]))->toThrow(QueryException::class);
});

it('mencatat riwayat penanganan dengan status_sebelum NULL pada baris pertama', function () {
    $pengaduan = buatPengaduan();
    $petugas = User::factory()->create(['role_id' => Role::factory()->create()->id_role]);
    $langkah1 = PenangananPengaduan::create([
        'pengaduan_id' => $pengaduan->id_pengaduan, 'user_id' => $petugas->id_user,
        'status_sebelum' => null, 'status_sesudah' => StatusPengaduan::Diterima->value,
        'tanggal_penanganan' => '2026-08-16', 'catatan' => 'Laporan diterima, dijadwalkan survei.',
    ]);
    PenangananPengaduan::create([
        'pengaduan_id' => $pengaduan->id_pengaduan, 'user_id' => $petugas->id_user,
        'status_sebelum' => StatusPengaduan::Diterima->value, 'status_sesudah' => StatusPengaduan::Diproses->value,
        'tanggal_penanganan' => '2026-08-20', 'catatan' => 'Perbaikan dimulai.',
    ]);

    expect($langkah1->status_sebelum)->toBeNull()
        ->and($langkah1->status_sesudah)->toBe(StatusPengaduan::Diterima)
        ->and($langkah1->petugas->id_user)->toBe($petugas->id_user)
        ->and($pengaduan->penanganan()->count())->toBe(2);

    // petugas penangan tidak dapat dihapus selagi punya jejak penanganan (RESTRICT).
    expect(fn () => $petugas->forceDelete())->toThrow(QueryException::class);
});

it('menyapu riwayat penanganan saat pengaduan dihapus permanen (CASCADE)', function () {
    $pengaduan = buatPengaduan();
    $petugas = User::factory()->create(['role_id' => Role::factory()->create()->id_role]);
    PenangananPengaduan::create([
        'pengaduan_id' => $pengaduan->id_pengaduan, 'user_id' => $petugas->id_user,
        'status_sesudah' => StatusPengaduan::Diterima->value,
        'tanggal_penanganan' => '2026-08-16', 'catatan' => 'x',
    ]);

    $pengaduan->forceDelete();

    expect(PenangananPengaduan::where('pengaduan_id', $pengaduan->id_pengaduan)->count())->toBe(0);
});

it('menautkan berkas ke infrastruktur, pengaduan, dan penanganan lewat pivot', function () {
    $pengaduan = buatPengaduan();
    $petugas = User::factory()->create(['role_id' => Role::factory()->create()->id_role]);
    $langkah = PenangananPengaduan::create([
        'pengaduan_id' => $pengaduan->id_pengaduan, 'user_id' => $petugas->id_user,
        'status_sesudah' => StatusPengaduan::Selesai->value,
        'tanggal_penanganan' => '2026-09-01', 'catatan' => 'Selesai.',
    ]);
    $foto = Berkas::create(['uuid' => (string) Str::uuid(), 'nama_file' => 'f.jpg', 'path' => 'x/f.jpg', 'mime' => 'image/jpeg', 'ekstensi' => 'jpg', 'ukuran' => 100]);
    $ba = Berkas::create(['uuid' => (string) Str::uuid(), 'nama_file' => 'ba.pdf', 'path' => 'x/ba.pdf', 'mime' => 'application/pdf', 'ekstensi' => 'pdf', 'ukuran' => 200]);

    $pengaduan->berkas()->attach($foto->id_berkas, ['peran' => 'bukti']);
    $langkah->berkas()->attach($ba->id_berkas, ['peran' => 'berita_acara']);

    expect($pengaduan->berkas()->first()->pivot->peran)->toBe('bukti')
        ->and($langkah->berkas()->count())->toBe(1);
});

it('mengaktifkan soft delete pada infrastruktur & pengaduan, tidak pada penanganan_pengaduan', function () {
    expect(in_array(SoftDeletes::class, class_uses_recursive(Infrastruktur::class), true))->toBeTrue()
        ->and(in_array(SoftDeletes::class, class_uses_recursive(Pengaduan::class), true))->toBeTrue()
        ->and(in_array(SoftDeletes::class, class_uses_recursive(PenangananPengaduan::class), true))->toBeFalse();
});
