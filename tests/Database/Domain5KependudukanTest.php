<?php

/*
 * Task 3.1 -- DOMAIN 5 (Kependudukan).
 *
 * Tabel: transmigran, anggota_keluarga, rumah, riwayat_penghunian,
 * riwayat_kepala_keluarga (+ pivot transmigran_berkas, rumah_berkas).
 *
 * Berjalan di MySQL/MariaDB nyata. Kecocokan kolom/indeks/FK dijaga terpisah
 * oleh `php artisan sim:banding-skema`. `buatSp()` di-share dari Domain2WilayahSpTest.
 */

use App\Enums\AlasanPergantianKK;
use App\Enums\HubunganAnggotaKeluarga;
use App\Enums\JenisKelamin;
use App\Enums\StatusAnggotaKeluarga;
use App\Enums\StatusSertifikat;
use App\Enums\StatusTinggal;
use App\Models\AnggotaKeluarga;
use App\Models\RiwayatKepalaKeluarga;
use App\Models\RiwayatPenghunian;
use App\Models\Rumah;
use App\Models\Transmigran;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

require_once __DIR__.'/DatabaseHelpers.php';

it('membuat kelima tabel + dua pivot batch ini', function () {
    foreach ([
        'transmigran', 'anggota_keluarga', 'rumah', 'riwayat_penghunian',
        'riwayat_kepala_keluarga', 'transmigran_berkas', 'rumah_berkas',
    ] as $tabel) {
        expect(Schema::hasTable($tabel))->toBeTrue("tabel {$tabel} tidak dibuat");
    }
});

it('memakai PK tunggal, uuid route key untuk transmigran & rumah', function () {
    expect((new Transmigran)->getKeyName())->toBe('id_transmigran')
        ->and((new Transmigran)->getRouteKeyName())->toBe('uuid')
        ->and((new Rumah)->getKeyName())->toBe('id_rumah')
        ->and((new Rumah)->getRouteKeyName())->toBe('uuid')
        ->and((new AnggotaKeluarga)->getKeyName())->toBe('id_anggota_keluarga')
        ->and((new RiwayatPenghunian)->getKeyName())->toBe('id_riwayat_penghunian')
        ->and((new RiwayatKepalaKeluarga)->getKeyName())->toBe('id_riwayat_kepala_keluarga');
});

it('merangkai KK -> anggota -> SP -> daerah asal lewat kunci eksplisit', function () {
    $sp = buatSp();
    $kk = buatTransmigran($sp, ['daerah_asal_kabupaten_id' => $sp->desa->kecamatan->kabupaten->id_kabupaten]);
    $anggota = AnggotaKeluarga::create([
        'transmigran_id' => $kk->id_transmigran,
        'hubungan' => HubunganAnggotaKeluarga::Anak->value,
        'nama_lengkap' => 'Yosef Bere', 'jenis_kelamin' => JenisKelamin::LakiLaki->value,
    ]);

    expect($kk->satuanPermukiman->id_satuan_permukiman)->toBe($sp->id_satuan_permukiman)
        ->and($kk->daerahAsal->id_kabupaten)->toBe($sp->desa->kecamatan->kabupaten->id_kabupaten)
        ->and($kk->anggotaKeluarga->pluck('id_anggota_keluarga'))->toContain($anggota->id_anggota_keluarga)
        ->and($anggota->hubungan)->toBe(HubunganAnggotaKeluarga::Anak)
        ->and($anggota->fresh()->status)->toBe(StatusAnggotaKeluarga::Aktif)
        ->and($sp->transmigran->pluck('id_transmigran'))->toContain($kk->id_transmigran);
});

it('meng-cast ENUM kependudukan dan default status_sertifikat', function () {
    $kk = buatTransmigran();

    expect($kk->fresh()->status_sertifikat)->toBe(StatusSertifikat::BelumDidata)
        ->and($kk->status_tinggal)->toBe(StatusTinggal::Aktif);
});

it('menegakkan UNIQUE nik, no_kk, uuid pada transmigran', function () {
    $kk = buatTransmigran();

    expect(fn () => buatTransmigran(null, ['nik' => $kk->nik]))->toThrow(QueryException::class);
    expect(fn () => buatTransmigran(null, ['no_kk' => $kk->no_kk]))->toThrow(QueryException::class);
});

it('mengikat rumah ke KK satu-ke-satu (UNIQUE transmigran_id)', function () {
    $sp = buatSp();
    $kk = buatTransmigran($sp);
    $rumah = Rumah::create([
        'uuid' => (string) Str::uuid(), 'satuan_permukiman_id' => $sp->id_satuan_permukiman,
        'transmigran_id' => $kk->id_transmigran, 'kondisi' => 'Tidak Rusak', 'status_hunian' => 'Dihuni',
    ]);

    expect($kk->rumah->id_rumah)->toBe($rumah->id_rumah)
        ->and($rumah->penghuni->id_transmigran)->toBe($kk->id_transmigran);

    // rumah kedua untuk KK yang sama ditolak.
    expect(fn () => Rumah::create([
        'uuid' => (string) Str::uuid(), 'satuan_permukiman_id' => $sp->id_satuan_permukiman,
        'transmigran_id' => $kk->id_transmigran, 'kondisi' => 'Rusak Ringan', 'status_hunian' => 'Dihuni',
    ]))->toThrow(QueryException::class);
});

it('mengosongkan rumah saat KK dihapus (SET NULL), bukan menghapus rumah', function () {
    $sp = buatSp();
    $kk = buatTransmigran($sp);
    $rumah = Rumah::create([
        'uuid' => (string) Str::uuid(), 'satuan_permukiman_id' => $sp->id_satuan_permukiman,
        'transmigran_id' => $kk->id_transmigran, 'kondisi' => 'Tidak Rusak', 'status_hunian' => 'Dihuni',
    ]);

    $kk->forceDelete();

    expect($rumah->fresh()->transmigran_id)->toBeNull()
        ->and(Rumah::find($rumah->id_rumah))->not->toBeNull();
});

it('menghapus anggota keluarga saat KK dihapus permanen (CASCADE)', function () {
    $kk = buatTransmigran();
    AnggotaKeluarga::create([
        'transmigran_id' => $kk->id_transmigran, 'hubungan' => HubunganAnggotaKeluarga::Istri->value,
        'nama_lengkap' => 'Maria Bere',
    ]);

    $kk->forceDelete();

    expect(AnggotaKeluarga::withTrashed()->where('transmigran_id', $kk->id_transmigran)->count())->toBe(0);
});

it('menahan KK dengan riwayat penghunian / suksesi dari penghapusan (RESTRICT)', function () {
    $sp = buatSp();
    $kk = buatTransmigran($sp);
    $rumah = Rumah::create([
        'uuid' => (string) Str::uuid(), 'satuan_permukiman_id' => $sp->id_satuan_permukiman,
        'kondisi' => 'Tidak Rusak', 'status_hunian' => 'Dihuni',
    ]);
    RiwayatPenghunian::create([
        'rumah_id' => $rumah->id_rumah, 'transmigran_id' => $kk->id_transmigran,
        'tanggal_masuk' => '2015-06-01',
    ]);

    expect(fn () => $kk->forceDelete())->toThrow(QueryException::class);
});

it('menyimpan suksesi kepala keluarga dengan identitas didenormalisasi', function () {
    $kk = buatTransmigran();
    $riwayat = RiwayatKepalaKeluarga::create([
        'transmigran_id' => $kk->id_transmigran,
        'nik_lama' => '1111111111111111', 'nama_lama' => 'Mateus Bere',
        'nik_baru' => '2222222222222222', 'nama_baru' => 'Yosef Bere',
        'no_kk_lama' => '3333333333333333', 'no_kk_baru' => '4444444444444444',
        'tanggal_pergantian' => '2024-03-01', 'alasan' => AlasanPergantianKK::Meninggal->value,
        'hubungan_pengganti' => HubunganAnggotaKeluarga::Anak->value,
    ]);

    expect($riwayat->alasan)->toBe(AlasanPergantianKK::Meninggal)
        ->and($riwayat->hubungan_pengganti)->toBe(HubunganAnggotaKeluarga::Anak)
        ->and($kk->riwayatKepalaKeluarga->pluck('id_riwayat_kepala_keluarga'))
        ->toContain($riwayat->id_riwayat_kepala_keluarga);
});

it('mengaktifkan soft delete pada transmigran/anggota_keluarga/rumah, tidak pada tabel riwayat', function () {
    expect(in_array(SoftDeletes::class, class_uses_recursive(Transmigran::class), true))->toBeTrue()
        ->and(in_array(SoftDeletes::class, class_uses_recursive(AnggotaKeluarga::class), true))->toBeTrue()
        ->and(in_array(SoftDeletes::class, class_uses_recursive(Rumah::class), true))->toBeTrue()
        ->and(in_array(SoftDeletes::class, class_uses_recursive(RiwayatPenghunian::class), true))->toBeFalse()
        ->and(in_array(SoftDeletes::class, class_uses_recursive(RiwayatKepalaKeluarga::class), true))->toBeFalse();
});
